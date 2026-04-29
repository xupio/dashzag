<?php

namespace App\Services;

use App\Models\Earning;
use App\Models\Miner;
use App\Models\MinerStatusHistory;
use App\Models\ShareHolding;
use App\Models\ShareListing;
use App\Models\ShareMarketTransaction;
use App\Models\ShareSale;
use App\Models\User;
use App\Notifications\ActivityFeedNotification;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ShareMarketService
{
    public function createListing(User $seller, Miner $miner, int $quantity, float $pricePerShare): ShareListing
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Listing quantity must be greater than zero.');
        }

        if ($pricePerShare <= 0) {
            throw new InvalidArgumentException('Listing price per share must be greater than zero.');
        }

        if ($miner->status !== 'secondary_market_open') {
            throw new InvalidArgumentException('This miner is not open for secondary market listings yet.');
        }

        return DB::transaction(function () use ($seller, $miner, $quantity, $pricePerShare): ShareListing {
            /** @var ShareHolding|null $holding */
            $holding = ShareHolding::query()
                ->where('user_id', $seller->id)
                ->where('miner_id', $miner->id)
                ->lockForUpdate()
                ->first();

            if (! $holding) {
                throw new InvalidArgumentException('Seller does not own shares in this miner.');
            }

            if ($holding->transferable_quantity < $quantity) {
                throw new InvalidArgumentException('Seller does not have enough transferable shares.');
            }

            $grossAmount = round($quantity * $pricePerShare, 2);
            $feePercent = (float) ($miner->secondary_market_fee_percent ?? 0);
            $feeAmount = round($grossAmount * ($feePercent / 100), 2);
            $sellerNet = round($grossAmount - $feeAmount, 2);

            $listing = ShareListing::create([
                'seller_user_id' => $seller->id,
                'miner_id' => $miner->id,
                'share_holding_id' => $holding->id,
                'quantity' => $quantity,
                'remaining_quantity' => $quantity,
                'price_per_share' => $pricePerShare,
                'total_price' => $grossAmount,
                'platform_fee_percent' => $feePercent,
                'platform_fee_amount' => $feeAmount,
                'seller_net_amount' => $sellerNet,
                'status' => 'active',
                'listed_at' => now(),
            ]);

            $holding->increment('locked_quantity', $quantity);

            return $listing->fresh();
        });
    }

    public function cancelListing(ShareListing $listing): ShareListing
    {
        if (! in_array($listing->status, ['active', 'partially_sold'], true)) {
            throw new InvalidArgumentException('Only active listings can be cancelled.');
        }

        return DB::transaction(function () use ($listing): ShareListing {
            /** @var ShareHolding $holding */
            $holding = ShareHolding::query()
                ->whereKey($listing->share_holding_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($listing->remaining_quantity > 0) {
                $holding->locked_quantity = max(0, (int) $holding->locked_quantity - (int) $listing->remaining_quantity);
                $holding->save();
            }

            $listing->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'remaining_quantity' => 0,
            ]);

            return $listing->fresh();
        });
    }

    public function completeSale(ShareListing $listing, User $buyer, int $quantity): ShareSale
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Sale quantity must be greater than zero.');
        }

        if ($listing->seller_user_id === $buyer->id) {
            throw new InvalidArgumentException('Seller cannot buy their own listing.');
        }

        $sale = DB::transaction(function () use ($listing, $buyer, $quantity): ShareSale {
            /** @var ShareListing $lockedListing */
            $lockedListing = ShareListing::query()
                ->whereKey($listing->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($lockedListing->status, ['active', 'partially_sold'], true)) {
                throw new InvalidArgumentException('Listing is not available for sale.');
            }

            if ($lockedListing->remaining_quantity < $quantity) {
                throw new InvalidArgumentException('Listing does not have enough remaining shares.');
            }

            $availableEarnings = Earning::query()
                ->where('user_id', $buyer->id)
                ->where('status', 'available')
                ->orderBy('earned_on')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            /** @var ShareHolding $sellerHolding */
            $sellerHolding = ShareHolding::query()
                ->whereKey($lockedListing->share_holding_id)
                ->lockForUpdate()
                ->firstOrFail();

            /** @var ShareHolding|null $buyerHolding */
            $buyerHolding = ShareHolding::query()
                ->where('user_id', $buyer->id)
                ->where('miner_id', $lockedListing->miner_id)
                ->lockForUpdate()
                ->first();

            $grossAmount = round($quantity * (float) $lockedListing->price_per_share, 2);
            $feePercent = (float) $lockedListing->platform_fee_percent;
            $feeAmount = round($grossAmount * ($feePercent / 100), 2);
            $sellerNet = round($grossAmount - $feeAmount, 2);
            $availableBalance = round((float) $availableEarnings->sum('amount'), 2);

            if ($grossAmount > $availableBalance) {
                throw new InvalidArgumentException('Buyer does not have enough available earnings to fund this secondary market purchase.');
            }

            $sale = ShareSale::create([
                'listing_id' => $lockedListing->id,
                'miner_id' => $lockedListing->miner_id,
                'seller_user_id' => $lockedListing->seller_user_id,
                'buyer_user_id' => $buyer->id,
                'quantity' => $quantity,
                'price_per_share' => $lockedListing->price_per_share,
                'gross_amount' => $grossAmount,
                'platform_fee_percent' => $feePercent,
                'platform_fee_amount' => $feeAmount,
                'seller_net_amount' => $sellerNet,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            $sellerHolding->quantity = max(0, (int) $sellerHolding->quantity - $quantity);
            $sellerHolding->locked_quantity = max(0, (int) $sellerHolding->locked_quantity - $quantity);
            $sellerHolding->status = $sellerHolding->quantity > 0 ? 'active' : 'exited';
            $sellerHolding->last_acquired_at = $sellerHolding->last_acquired_at ?: now();
            $sellerHolding->save();

            if (! $buyerHolding) {
                $buyerHolding = ShareHolding::create([
                    'user_id' => $buyer->id,
                    'miner_id' => $lockedListing->miner_id,
                    'quantity' => 0,
                    'locked_quantity' => 0,
                    'avg_buy_price' => 0,
                    'status' => 'active',
                ]);
            }

            $existingQuantity = (int) $buyerHolding->quantity;
            $existingValue = round($existingQuantity * (float) $buyerHolding->avg_buy_price, 2);
            $newValue = round($quantity * (float) $lockedListing->price_per_share, 2);
            $newQuantity = $existingQuantity + $quantity;
            $buyerHolding->quantity = $newQuantity;
            $buyerHolding->avg_buy_price = $newQuantity > 0
                ? round(($existingValue + $newValue) / $newQuantity, 2)
                : 0;
            $buyerHolding->status = 'active';
            $buyerHolding->last_acquired_at = now();
            $buyerHolding->save();

            $lockedListing->remaining_quantity = max(0, (int) $lockedListing->remaining_quantity - $quantity);
            $lockedListing->status = match (true) {
                $lockedListing->remaining_quantity === 0 => 'sold',
                $lockedListing->remaining_quantity < $lockedListing->quantity => 'partially_sold',
                default => 'active',
            };
            $lockedListing->sold_at = $lockedListing->remaining_quantity === 0 ? now() : $lockedListing->sold_at;
            $lockedListing->platform_fee_amount = round(
                ((float) $lockedListing->quantity - (float) $lockedListing->remaining_quantity) * (float) $lockedListing->price_per_share * ($feePercent / 100),
                2
            );
            $lockedListing->seller_net_amount = round(
                ((float) $lockedListing->quantity - (float) $lockedListing->remaining_quantity) * (float) $lockedListing->price_per_share - (float) $lockedListing->platform_fee_amount,
                2
            );
            $lockedListing->save();

            ShareMarketTransaction::create([
                'user_id' => $buyer->id,
                'type' => 'secondary_share_purchase',
                'reference_type' => ShareSale::class,
                'reference_id' => $sale->id,
                'amount' => $grossAmount,
                'currency' => 'USD',
                'status' => 'completed',
                'meta' => [
                    'miner_id' => $lockedListing->miner_id,
                    'quantity' => $quantity,
                    'funding_source' => 'available_earnings',
                ],
            ]);

            ShareMarketTransaction::create([
                'user_id' => $lockedListing->seller_user_id,
                'type' => 'secondary_share_sale',
                'reference_type' => ShareSale::class,
                'reference_id' => $sale->id,
                'amount' => $sellerNet,
                'currency' => 'USD',
                'status' => 'completed',
                'meta' => [
                    'miner_id' => $lockedListing->miner_id,
                    'quantity' => $quantity,
                ],
            ]);

            Earning::query()->create([
                'user_id' => $lockedListing->seller_user_id,
                'investment_id' => null,
                'payout_request_id' => null,
                'earned_on' => now()->toDateString(),
                'amount' => $sellerNet,
                'source' => 'secondary_share_sale',
                'status' => 'available',
                'notes' => 'Net proceeds from secondary market sale #'.$sale->id.' for '.($lockedListing->miner?->name ?? 'the selected miner').'.',
            ]);

            if ($feeAmount > 0) {
                ShareMarketTransaction::create([
                    'user_id' => null,
                    'type' => 'platform_fee',
                    'reference_type' => ShareSale::class,
                    'reference_id' => $sale->id,
                    'amount' => $feeAmount,
                    'currency' => 'USD',
                    'status' => 'completed',
                    'meta' => [
                        'miner_id' => $lockedListing->miner_id,
                        'quantity' => $quantity,
                        'funding_source' => 'available_earnings',
                    ],
                ]);
            }

            $this->allocateBuyerWalletFunds(
                $buyer,
                $availableEarnings,
                $grossAmount,
                $lockedListing->miner?->name ?? 'the selected miner',
                $sale
            );

            return $sale->fresh();
        });

        $this->notifySaleParticipants($sale);

        return $sale;
    }

    public function transitionMinerStatus(Miner $miner, string $newStatus, ?string $reason = null, ?User $actor = null): Miner
    {
        $oldStatus = $miner->status;

        if ($oldStatus === $newStatus) {
            return $miner;
        }

        return DB::transaction(function () use ($miner, $newStatus, $reason, $actor, $oldStatus): Miner {
            $miner->status = $newStatus;

            match ($newStatus) {
                'open' => $miner->opened_at = $miner->opened_at ?? now(),
                'sold_out' => $miner->sold_out_at = $miner->sold_out_at ?? now(),
                'mature' => $miner->matured_at = $miner->matured_at ?? now(),
                'secondary_market_open' => $miner->secondary_market_opened_at = $miner->secondary_market_opened_at ?? now(),
                'closed' => $miner->closed_at = $miner->closed_at ?? now(),
                default => null,
            };

            $miner->save();

            MinerStatusHistory::create([
                'miner_id' => $miner->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'reason' => $reason,
                'changed_by' => $actor?->id,
            ]);

            return $miner->fresh();
        });
    }

    private function notifySaleParticipants(ShareSale $sale): void
    {
        $sale->loadMissing(['miner', 'buyer', 'seller']);

        $minerName = $sale->miner?->name ?? 'the selected miner';
        $quantity = (int) $sale->quantity;
        $pricePerShare = (float) $sale->price_per_share;
        $grossAmount = (float) $sale->gross_amount;
        $sellerNet = (float) $sale->seller_net_amount;
        $feeAmount = (float) $sale->platform_fee_amount;

        if ($sale->buyer) {
            $sale->buyer->notify(new ActivityFeedNotification([
                'category' => 'investment',
                'status' => 'success',
                'subject' => 'Secondary market purchase completed',
                'message' => 'Your share purchase from '.$minerName.' has been completed successfully.',
                'context_label' => 'Shares purchased',
                'context_value' => $quantity.' share'.($quantity === 1 ? '' : 's').' at $'.number_format($pricePerShare, 2).' each',
                'amount' => $grossAmount,
                'amount_label' => 'Total paid',
                'status_line' => 'You now hold the purchased shares in your market inventory.',
                'notes_line' => 'Open Share Market to review the updated holding and future resale options.',
                'action_text' => 'Open Share Market',
                'action_url' => route('share-market.index'),
            ]));
        }

        if ($sale->seller) {
            $sale->seller->notify(new ActivityFeedNotification([
                'category' => 'investment',
                'status' => 'success',
                'subject' => 'Secondary market sale completed',
                'message' => 'Your share listing for '.$minerName.' has been purchased successfully.',
                'context_label' => 'Shares sold',
                'context_value' => $quantity.' share'.($quantity === 1 ? '' : 's').' at $'.number_format($pricePerShare, 2).' each',
                'amount' => $sellerNet,
                'amount_label' => 'Net proceeds',
                'status_line' => 'Platform fee deducted: $'.number_format($feeAmount, 2),
                'notes_line' => 'Your net proceeds are now available in wallet earnings. Open Share Market to review the updated listing status and remaining holdings.',
                'action_text' => 'Open Share Market',
                'action_url' => route('share-market.index'),
            ]));
        }
    }

    private function allocateBuyerWalletFunds(
        User $buyer,
        $availableEarnings,
        float $grossAmount,
        string $minerName,
        ShareSale $sale
    ): void {
        $remaining = round($grossAmount, 2);
        $note = 'Allocated to secondary market purchase #'.$sale->id.' for '.$minerName.'.';

        foreach ($availableEarnings as $earning) {
            if ($remaining <= 0) {
                break;
            }

            $earningAmount = round((float) $earning->amount, 2);

            if ($earningAmount <= $remaining) {
                $earning->forceFill([
                    'status' => 'market_spent',
                    'notes' => trim(($earning->notes ? $earning->notes.' ' : '').$note),
                ])->save();

                $remaining = round($remaining - $earningAmount, 2);

                continue;
            }

            $earning->forceFill([
                'amount' => round($earningAmount - $remaining, 2),
            ])->save();

            Earning::query()->create([
                'user_id' => $buyer->id,
                'investment_id' => $earning->investment_id,
                'payout_request_id' => null,
                'earned_on' => $earning->earned_on,
                'amount' => $remaining,
                'source' => 'secondary_share_purchase',
                'status' => 'market_spent',
                'notes' => $note,
            ]);

            $remaining = 0;
        }
    }
}
