<?php

namespace App\Services\Network;

use App\Enums\CoreStatus;
use App\Models\CoreJoint;
use App\Models\FiberCable;
use App\Models\FiberCore;
use App\Models\NetworkNode;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use RuntimeException;

class CoreManagementService
{
    public function paginate(
        ?int $cableId = null,
        ?string $status = null,
        int $perPage = 25,
    ): LengthAwarePaginator {
        return FiberCore::query()
            ->with(['cable', 'tubeColor', 'sourceNode', 'targetNode'])
            ->when($cableId, fn ($q) => $q->where('cable_id', $cableId))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy('cable_id')
            ->orderBy('tube_number')
            ->orderBy('core_number')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id): FiberCore
    {
        return FiberCore::query()
            ->with(['cable', 'tubeColor', 'sourceNode', 'targetNode', 'cableTube.tubeColor'])
            ->findOrFail($id);
    }

    /** @param array<string, mixed> $data */
    public function update(FiberCore $core, array $data): FiberCore
    {
        if ($core->status === CoreStatus::Used && ($data['status'] ?? null) !== CoreStatus::Used->value) {
            if ($this->isReferenced($core)) {
                throw new RuntimeException(__('hfnms.core_cannot_change_used_status'));
            }
        }

        $core->update([
            'status' => $data['status'],
            'source_node_id' => $data['source_node_id'] ?? null,
            'target_node_id' => $data['target_node_id'] ?? null,
            'loss_db' => $data['loss_db'] ?? null,
        ]);

        return $core->fresh(['cable', 'tubeColor', 'sourceNode', 'targetNode', 'cableTube.tubeColor']);
    }

    public function delete(FiberCore $core): void
    {
        if ($this->isReferenced($core)) {
            throw new RuntimeException(__('hfnms.core_delete_referenced'));
        }

        if ($core->status === CoreStatus::Used) {
            throw new RuntimeException(__('hfnms.core_delete_in_use'));
        }

        $core->delete();
    }

    public function isReferenced(FiberCore $core): bool
    {
        return CoreJoint::query()
            ->where(function ($query) use ($core) {
                $query->where('fiber_core_a_id', $core->id)
                    ->orWhere('fiber_core_b_id', $core->id);
            })
            ->exists()
            || $core->links()->exists()
            || $core->otdrRecords()->exists();
    }

    /** @return Collection<int, FiberCable> */
    public function cableOptions(): Collection
    {
        return FiberCable::query()
            ->orderBy('code')
            ->get(['id', 'code', 'name']);
    }

    /** @return SupportCollection<int, NetworkNode> */
    public function nodeOptions(): SupportCollection
    {
        return NetworkNode::query()
            ->orderBy('type')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type']);
    }

    /** @return array<string, int> */
    public function statusCounts(): array
    {
        $counts = [];

        foreach (FiberCore::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status') as $status => $total) {
            $counts[$status] = (int) $total;
        }

        return $counts;
    }
}
