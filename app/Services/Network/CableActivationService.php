<?php

namespace App\Services\Network;

use App\Enums\NetworkLinkType;
use App\Models\FiberCable;
use App\Models\FiberCore;
use App\Models\NetworkLink;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CableActivationService
{
    /** @return list<array<string, mixed>> */
    public function plannedCablesAtNode(int $nodeId): array
    {
        return FiberCable::query()
            ->with(['startNode:id,code', 'endNode:id,code'])
            ->where('status', 'planned')
            ->where(function ($query) use ($nodeId) {
                $query->where('start_node_id', $nodeId)
                    ->orWhere('end_node_id', $nodeId);
            })
            ->orderBy('code')
            ->get()
            ->map(fn (FiberCable $cable) => [
                'id' => $cable->id,
                'code' => $cable->code,
                'name' => $cable->name,
                'core_count' => $cable->core_count,
                'start_code' => $cable->startNode?->code,
                'end_code' => $cable->endNode?->code,
                'other_node_code' => (int) $cable->start_node_id === $nodeId
                    ? $cable->endNode?->code
                    : $cable->startNode?->code,
            ])
            ->values()
            ->all();
    }

    public function activate(FiberCable $cable): FiberCable
    {
        if ($cable->status === 'active') {
            return $cable;
        }

        if ($cable->status !== 'planned') {
            throw new InvalidArgumentException(__('hfnms.cable_activate_invalid_status'));
        }

        if (! $cable->start_node_id || ! $cable->end_node_id) {
            throw new InvalidArgumentException(__('hfnms.cable_activate_requires_endpoints'));
        }

        return DB::transaction(function () use ($cable) {
            $cable->update([
                'status' => 'active',
                'installed_at' => $cable->installed_at ?? now()->toDateString(),
            ]);

            $this->ensureNetworkLink($cable->fresh());

            return $cable->fresh(['startNode', 'endNode']);
        });
    }

    /** @param list<int> $cableIds */
    public function activateMany(array $cableIds): void
    {
        foreach (array_unique(array_filter($cableIds)) as $cableId) {
            $cable = FiberCable::query()->find($cableId);

            if ($cable && $cable->status === 'planned') {
                $this->activate($cable);
            }
        }
    }

    /** @param list<int|null> $coreIds */
    public function activateCablesForCores(int ...$coreIds): void
    {
        $cableIds = FiberCore::query()
            ->whereIn('id', array_filter($coreIds))
            ->pluck('cable_id')
            ->unique()
            ->all();

        $this->activateMany($cableIds);
    }

    /** @return Collection<int, FiberCable> */
    public function cablesAtNode(int $nodeId, bool $includePlanned = true): Collection
    {
        return FiberCable::query()
            ->where(function ($query) use ($nodeId) {
                $query->where('start_node_id', $nodeId)
                    ->orWhere('end_node_id', $nodeId);
            })
            ->when(! $includePlanned, fn ($query) => $query->where('status', 'active'))
            ->orderBy('code')
            ->get();
    }

    private function ensureNetworkLink(FiberCable $cable): void
    {
        $exists = NetworkLink::query()
            ->where('link_type', NetworkLinkType::FiberCable->value)
            ->where('cable_id', $cable->id)
            ->where('source_node_id', $cable->start_node_id)
            ->where('target_node_id', $cable->end_node_id)
            ->exists();

        if ($exists) {
            return;
        }

        NetworkLink::query()->create([
            'source_node_id' => $cable->start_node_id,
            'target_node_id' => $cable->end_node_id,
            'link_type' => NetworkLinkType::FiberCable->value,
            'cable_id' => $cable->id,
            'length_meters' => $cable->length_meters,
            'status' => 'active',
        ]);
    }
}
