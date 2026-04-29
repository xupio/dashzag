<?php

namespace App\Services;

use App\Models\Miner;
use App\Models\MinerStatusHistory;
use App\Models\ShareHolding;
use App\Models\UserInvestment;
use Illuminate\Support\Facades\DB;

class ShareHoldingSyncService
{
    public function syncFromInvestment(UserInvestment $investment): void
    {
        $pairs = [
            [
                'user_id' => (int) $investment->user_id,
                'miner_id' => (int) $investment->miner_id,
            ],
        ];

        if ($investment->wasChanged(['user_id', 'miner_id'])) {
            $pairs[] = [
                'user_id' => (int) $investment->getOriginal('user_id'),
                'miner_id' => (int) $investment->getOriginal('miner_id'),
            ];
        }

        foreach ($pairs as $pair) {
            if ($pair['user_id'] <= 0 || $pair['miner_id'] <= 0) {
                continue;
            }

            $this->syncForUserAndMiner($pair['user_id'], $pair['miner_id']);
        }
    }

    public function syncForUserAndMiner(int $userId, int $minerId): void
    {
        DB::transaction(function () use ($userId, $minerId): void {
            $activeInvestments = UserInvestment::query()
                ->where('user_id', $userId)
                ->where('miner_id', $minerId)
                ->where('status', 'active')
                ->lockForUpdate()
                ->get();

            /** @var ShareHolding|null $holding */
            $holding = ShareHolding::query()
                ->where('user_id', $userId)
                ->where('miner_id', $minerId)
                ->lockForUpdate()
                ->first();

            $totalQuantity = (int) $activeInvestments->sum('shares_owned');
            $totalAmount = (float) $activeInvestments->sum('amount');
            $lastAcquiredAt = $activeInvestments
                ->pluck('subscribed_at')
                ->filter()
                ->max();

            if ($totalQuantity === 0) {
                if ($holding) {
                    if ((int) $holding->locked_quantity > 0) {
                        $holding->quantity = 0;
                        $holding->avg_buy_price = 0;
                        $holding->status = 'inactive';
                        $holding->last_acquired_at = $holding->last_acquired_at;
                        $holding->save();
                    } else {
                        $holding->delete();
                    }
                }

                $this->syncMinerSharesSold($minerId);

                return;
            }

            $avgBuyPrice = round($totalAmount / $totalQuantity, 2);

            if (! $holding) {
                ShareHolding::query()->create([
                    'user_id' => $userId,
                    'miner_id' => $minerId,
                    'quantity' => $totalQuantity,
                    'locked_quantity' => 0,
                    'avg_buy_price' => $avgBuyPrice,
                    'status' => 'active',
                    'last_acquired_at' => $lastAcquiredAt,
                ]);
            } else {
                $holding->quantity = $totalQuantity;
                $holding->avg_buy_price = $avgBuyPrice;
                $holding->status = 'active';
                $holding->last_acquired_at = $lastAcquiredAt;
                $holding->save();
            }

            $this->syncMinerSharesSold($minerId);
        });
    }

    public function syncMinerSharesSold(int $minerId): void
    {
        /** @var Miner|null $miner */
        $miner = Miner::query()->lockForUpdate()->find($minerId);

        if (! $miner) {
            return;
        }

        $miner->shares_sold = (int) UserInvestment::query()
            ->where('miner_id', $minerId)
            ->where('status', 'active')
            ->sum('shares_owned');

        $miner->save();

        $this->syncMinerAvailabilityStatus($miner);
    }

    private function syncMinerAvailabilityStatus(Miner $miner): void
    {
        $targetStatus = $this->determineAvailabilityStatus($miner);

        if (! $targetStatus || $targetStatus === $miner->status) {
            return;
        }

        $oldStatus = $miner->status;
        $miner->status = $targetStatus;

        if ($targetStatus === 'open') {
            $miner->opened_at = $miner->opened_at ?? now();
            $miner->sold_out_at = null;
        }

        if ($targetStatus === 'sold_out') {
            $miner->sold_out_at = $miner->sold_out_at ?? now();
        }

        $miner->save();

        MinerStatusHistory::query()->create([
            'miner_id' => $miner->id,
            'old_status' => $oldStatus,
            'new_status' => $targetStatus,
            'reason' => 'Automatically synced from active share sales progress.',
            'changed_by' => null,
        ]);
    }

    private function determineAvailabilityStatus(Miner $miner): ?string
    {
        if ((int) $miner->total_shares <= 0) {
            return null;
        }

        if (in_array($miner->status, ['maintenance', 'closed', 'mature', 'secondary_market_open'], true)) {
            return null;
        }

        $sharesSold = (int) $miner->shares_sold;
        $totalShares = max(1, (int) $miner->total_shares);
        $nearCapacityThreshold = max(1, min(100, (int) ($miner->near_capacity_threshold_percent ?? 90)));
        $soldPercent = ($sharesSold / $totalShares) * 100;

        if ($sharesSold >= $totalShares) {
            return 'sold_out';
        }

        if ($soldPercent >= $nearCapacityThreshold) {
            return 'nearly_full';
        }

        if (in_array($miner->status, ['active', 'open', 'nearly_full', 'sold_out'], true)) {
            return 'open';
        }

        return null;
    }
}
