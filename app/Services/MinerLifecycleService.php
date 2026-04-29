<?php

namespace App\Services;

use App\Models\Miner;
use App\Models\MinerStatusHistory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MinerLifecycleService
{
    public function syncEligibleMiners(): Collection
    {
        return Miner::query()
            ->whereIn('status', ['sold_out', 'mature'])
            ->orderBy('id')
            ->get()
            ->map(fn (Miner $miner) => $this->syncForMiner($miner));
    }

    public function syncForMiner(Miner $miner): Miner
    {
        return DB::transaction(function () use ($miner): Miner {
            /** @var Miner $lockedMiner */
            $lockedMiner = Miner::query()->lockForUpdate()->findOrFail($miner->id);

            if ($lockedMiner->status === 'sold_out' && $this->hasReachedMaturity($lockedMiner)) {
                $this->transitionStatus(
                    $lockedMiner,
                    'mature',
                    'Automatically matured after the configured sold-out operating window.'
                );
            }

            if ($lockedMiner->status === 'mature') {
                $this->transitionStatus(
                    $lockedMiner,
                    'secondary_market_open',
                    'Automatically opened the secondary market after miner maturity.'
                );
            }

            return $lockedMiner->fresh();
        });
    }

    private function hasReachedMaturity(Miner $miner): bool
    {
        if (! $miner->sold_out_at) {
            return false;
        }

        $maturityDays = max(0, (int) ($miner->maturity_days ?? 0));

        return $miner->sold_out_at->copy()->addDays($maturityDays)->lessThanOrEqualTo(now());
    }

    private function transitionStatus(Miner $miner, string $newStatus, string $reason): void
    {
        if ($miner->status === $newStatus) {
            return;
        }

        $oldStatus = $miner->status;
        $miner->status = $newStatus;

        if ($newStatus === 'mature') {
            $miner->matured_at = $miner->matured_at ?? now();
        }

        if ($newStatus === 'secondary_market_open') {
            $miner->secondary_market_opened_at = $miner->secondary_market_opened_at ?? now();
        }

        $miner->save();

        MinerStatusHistory::query()->create([
            'miner_id' => $miner->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'reason' => $reason,
            'changed_by' => null,
        ]);
    }
}
