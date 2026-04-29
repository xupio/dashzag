<?php

namespace App\Http\Controllers;

use App\Models\Miner;
use App\Models\ShareHolding;
use App\Models\ShareListing;
use App\Services\ShareMarketService;
use App\Support\MiningPlatform;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ShareMarketController extends Controller
{
    public function __construct(
        protected ShareMarketService $shareMarketService,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $walletSummary = MiningPlatform::walletSummary($user);
        $availableWalletBalance = round((float) ($walletSummary['available'] ?? 0), 2);

        $holdings = ShareHolding::query()
            ->with('miner')
            ->where('user_id', $user->id)
            ->orderByDesc('updated_at')
            ->get();

        $myListings = ShareListing::query()
            ->with('miner')
            ->where('seller_user_id', $user->id)
            ->latest('listed_at')
            ->get();

        $activeListings = ShareListing::query()
            ->with(['miner', 'seller'])
            ->whereIn('status', ['active', 'partially_sold'])
            ->where('remaining_quantity', '>', 0)
            ->latest('listed_at')
            ->get();

        $activeListings->each(function (ShareListing $listing) use ($availableWalletBalance, $user) {
            $listingTotal = round((float) $listing->remaining_quantity * (float) $listing->price_per_share, 2);
            $maxAffordableQuantity = (int) floor($availableWalletBalance / max((float) $listing->price_per_share, 0.01));

            $listing->setAttribute('listing_total', $listingTotal);
            $listing->setAttribute('max_affordable_quantity', max(0, min((int) $listing->remaining_quantity, $maxAffordableQuantity)));
            $listing->setAttribute('is_affordable', $listing->seller_user_id === $user->id || $availableWalletBalance >= $listingTotal || $listing->getAttribute('max_affordable_quantity') > 0);
        });

        $miners = Miner::query()
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'slug',
                'status',
                'share_price',
                'secondary_market_fee_percent',
                'total_shares',
                'shares_sold',
                'near_capacity_threshold_percent',
                'maturity_days',
                'sold_out_at',
                'matured_at',
                'secondary_market_opened_at',
            ])
            ->map(function (Miner $miner) {
                $soldPercent = (int) $miner->total_shares > 0
                    ? round(((int) $miner->shares_sold / max(1, (int) $miner->total_shares)) * 100, 1)
                    : 0.0;

                $maturityDueAt = $miner->sold_out_at?->copy()->addDays((int) ($miner->maturity_days ?? 0));
                $daysUntilMaturity = $maturityDueAt && $maturityDueAt->isFuture()
                    ? now()->diffInDays($maturityDueAt)
                    : 0;

                $miner->setAttribute('sold_percent', $soldPercent);
                $miner->setAttribute('maturity_due_at', $maturityDueAt);
                $miner->setAttribute('days_until_maturity', $daysUntilMaturity);
                $miner->setAttribute('status_label', str_replace('_', ' ', $miner->status));
                $miner->setAttribute('is_tradable', $miner->status === 'secondary_market_open');

                return $miner;
            });

        return view('pages.general.share-market', [
            'holdings' => $holdings,
            'myListings' => $myListings,
            'activeListings' => $activeListings,
            'miners' => $miners,
            'walletSummary' => $walletSummary,
        ]);
    }

    public function storeListing(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'miner_id' => ['required', 'exists:miners,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'price_per_share' => ['required', 'numeric', 'gt:0'],
        ]);

        try {
            $listing = $this->shareMarketService->createListing(
                $request->user(),
                Miner::query()->findOrFail($validated['miner_id']),
                (int) $validated['quantity'],
                (float) $validated['price_per_share'],
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors([
                'share_market' => $exception->getMessage(),
            ]);
        }

        return back()->with('status', "Listing #{$listing->id} created successfully.");
    }

    public function cancelListing(Request $request, ShareListing $listing): RedirectResponse
    {
        abort_unless($listing->seller_user_id === $request->user()->id, 403);

        try {
            $this->shareMarketService->cancelListing($listing);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors([
                'share_market' => $exception->getMessage(),
            ]);
        }

        return back()->with('status', "Listing #{$listing->id} cancelled.");
    }

    public function buyListing(Request $request, ShareListing $listing): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $sale = $this->shareMarketService->completeSale(
                $listing,
                $request->user(),
                (int) $validated['quantity'],
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors([
                'share_market' => $exception->getMessage(),
            ]);
        }

        return back()->with('status', "Purchase completed. Sale #{$sale->id} recorded.");
    }
}
