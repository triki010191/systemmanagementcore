<?php

namespace App\Services\Network;

use App\Enums\CoreJointType;
use App\Enums\CoreStatus;
use App\Enums\NetworkNodeType;
use App\Models\CoreJoint;
use App\Models\FiberCable;
use App\Models\FiberCore;
use App\Models\NetworkNode;
use App\Models\Odc;
use App\Models\Odp;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CoreJointService
{
    public function __construct(
        private readonly CableActivationService $cableActivationService,
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return CoreJoint::query()
            ->with([
                'sourceNode',
                'targetNode',
                'networkNode',
                'odc.networkNode',
                'odp.networkNode',
                'coreA.cable',
                'coreB.cable',
            ])
            ->latest()
            ->paginate($perPage);
    }

    /** @return Collection<int, NetworkNode> */
    public function sourceNodeOptions(CoreJointType $jointType): Collection
    {
        if ($jointType === CoreJointType::OdcToOdp) {
            return $this->otbNodeOptions();
        }

        return NetworkNode::query()
            ->where('type', $jointType->sourceType())
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type', 'latitude', 'longitude', 'parent_id']);
    }

    /** @return Collection<int, NetworkNode> */
    public function otbNodeOptions(): Collection
    {
        return NetworkNode::query()
            ->where('type', NetworkNodeType::Otb)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type', 'latitude', 'longitude', 'parent_id']);
    }

    /** @return Collection<int, NetworkNode> */
    public function odcNodeOptionsForOtb(?int $otbNodeId = null): Collection
    {
        $query = NetworkNode::query()
            ->where('type', NetworkNodeType::Odc)
            ->orderBy('code');

        if ($otbNodeId) {
            $query->where('parent_id', $otbNodeId);
        }

        return $query->get(['id', 'code', 'name', 'type', 'latitude', 'longitude', 'parent_id']);
    }

    /** @return Collection<int, NetworkNode> */
    public function targetNodeOptions(CoreJointType $jointType, ?int $sourceNodeId = null): Collection
    {
        $query = NetworkNode::query()
            ->where('type', $jointType->targetType())
            ->orderBy('code');

        if ($sourceNodeId) {
            $query->where('parent_id', $sourceNodeId);
        }

        return $query->get(['id', 'code', 'name', 'type', 'latitude', 'longitude', 'parent_id']);
    }

    /** @return list<array<string, mixed>> */
    public function mapNodeMarkers(): array
    {
        return NetworkNode::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereIn('type', ['otb', 'odc', 'odp'])
            ->orderBy('type')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type', 'latitude', 'longitude'])
            ->map(fn (NetworkNode $node) => [
                'id' => $node->id,
                'code' => $node->code,
                'name' => $node->name,
                'type' => $node->type->value,
                'latitude' => (float) $node->latitude,
                'longitude' => (float) $node->longitude,
            ])
            ->values()
            ->all();
    }

    /** @return list<int> */
    public function sharedCoreNumbers(int $sourceNodeId, int $targetNodeId): array
    {
        return $this->joinableCoreNumbers($sourceNodeId, $targetNodeId);
    }

    public function coreAvailabilityHint(int $sourceNodeId, int $targetNodeId): ?string
    {
        $sourceCableCount = $this->cableIdsAtNode($sourceNodeId)->count();
        $targetCableCount = $this->cableIdsAtNode($targetNodeId)->count();

        if ($sourceCableCount === 0) {
            return __('hfnms.joint_no_cables_source');
        }

        if ($targetCableCount === 0) {
            return __('hfnms.joint_no_cables_target');
        }

        if ($this->joinableCoreNumbers($sourceNodeId, $targetNodeId) !== []) {
            return null;
        }

        $directCableCount = FiberCable::query()
            ->where(function ($query) use ($sourceNodeId, $targetNodeId) {
                $query->where(function ($inner) use ($sourceNodeId, $targetNodeId) {
                    $inner->where('start_node_id', $sourceNodeId)
                        ->where('end_node_id', $targetNodeId);
                })->orWhere(function ($inner) use ($sourceNodeId, $targetNodeId) {
                    $inner->where('start_node_id', $targetNodeId)
                        ->where('end_node_id', $sourceNodeId);
                });
            })
            ->count();

        if ($directCableCount > 0 && $targetCableCount <= $directCableCount) {
            return __('hfnms.joint_need_second_cable');
        }

        return __('hfnms.joint_no_joinable_cores');
    }

    /** @return list<array<string, mixed>> */
    public function cableOptionsAtNode(int $nodeId): array
    {
        return $this->cableActivationService->cablesAtNode($nodeId, true)
            ->map(fn (FiberCable $cable) => [
                'id' => $cable->id,
                'code' => $cable->code,
                'label' => $cable->code.' — '.$cable->name,
                'status' => $cable->status,
                'core_count' => $cable->core_count,
                'is_planned' => $cable->status === 'planned',
            ])
            ->values()
            ->all();
    }

    /** @return list<array<string, mixed>> */
    public function coreOptionsForCable(int $cableId): array
    {
        return FiberCore::query()
            ->where('cable_id', $cableId)
            ->orderBy('core_number')
            ->get(['id', 'core_number', 'status'])
            ->map(fn (FiberCore $core) => [
                'id' => $core->id,
                'core_number' => $core->core_number,
                'status' => $core->status->value,
                'label' => 'Core '.$core->core_number.' ('.$core->status->value.')',
            ])
            ->values()
            ->all();
    }

    public function maxSharedCoreNumber(int $sourceNodeId, int $targetNodeId): int
    {
        $sourceMax = (int) ($this->coresAtNode($sourceNodeId)->max('core_number') ?? 0);
        $targetMax = (int) ($this->coresAtNode($targetNodeId)->max('core_number') ?? 0);

        if ($sourceMax > 0 && $targetMax > 0) {
            return min($sourceMax, $targetMax);
        }

        return max($sourceMax, $targetMax);
    }

    /**
     * @return list<array{id: int, core_number: int, cable_code: string, tube_number: int, side: string}>
     */
    public function coreOptionsForJoint(
        CoreJointType $jointType,
        int $sourceNodeId,
        int $targetNodeId,
        ?int $exceptJointId = null,
    ): array {
        $this->validateHierarchy($jointType, $sourceNodeId, $targetNodeId);

        $usedCoreNumbers = $this->usedCoreNumbers($jointType, $sourceNodeId, $targetNodeId, $exceptJointId);

        return collect($this->joinableCoreNumbers($sourceNodeId, $targetNodeId))
            ->reject(fn (int $num) => $usedCoreNumbers->contains($num))
            ->map(function (int $coreNumber) use ($sourceNodeId, $targetNodeId) {
                $pair = $this->findCorePairAtNodes($sourceNodeId, $targetNodeId, $coreNumber);

                if (! $pair) {
                    return null;
                }

                $sourceCore = $pair['source'];
                $targetCore = $pair['target'];

                return [
                    'core_number' => $coreNumber,
                    'source_core_id' => $sourceCore->id,
                    'target_core_id' => $targetCore->id,
                    'source_cable' => $sourceCore->cable?->code,
                    'target_cable' => $targetCore->cable?->code,
                    'label' => sprintf(
                        'Core %d — %s ↔ %s',
                        $coreNumber,
                        $sourceCore->cable?->code ?? '?',
                        $targetCore->cable?->code ?? '?',
                    ),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public function find(int $id): CoreJoint
    {
        return CoreJoint::query()
            ->with(['sourceNode', 'targetNode', 'networkNode', 'odc.networkNode', 'odp.networkNode', 'coreA.cable', 'coreB.cable'])
            ->findOrFail($id);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): CoreJoint
    {
        return DB::transaction(function () use ($data) {
            $data = $this->normalizeJointData($data);
            $this->validateCorePair($data);

            $joint = CoreJoint::query()->create($data);

            $this->cableActivationService->activateCablesForCores(
                (int) $data['fiber_core_a_id'],
                (int) $data['fiber_core_b_id'],
            );
            $this->markCoresSpliced($joint);
            $this->syncAssetCoreUsage($joint);

            return $joint->load(['sourceNode', 'targetNode', 'networkNode', 'odc.networkNode', 'odp.networkNode', 'coreA.cable', 'coreB.cable']);
        });
    }

    /** @param array<string, mixed> $data */
    public function update(CoreJoint $joint, array $data): CoreJoint
    {
        return DB::transaction(function () use ($joint, $data) {
            $data = $this->normalizeJointData($data, $joint);
            $this->validateCorePair($data, $joint->id);

            $joint->update($data);
            $this->cableActivationService->activateCablesForCores(
                (int) $data['fiber_core_a_id'],
                (int) $data['fiber_core_b_id'],
            );
            $this->markCoresSpliced($joint->fresh());
            $this->syncAssetCoreUsage($joint->fresh());

            return $joint->fresh(['sourceNode', 'targetNode', 'networkNode', 'odc.networkNode', 'odp.networkNode', 'coreA.cable', 'coreB.cable']);
        });
    }

    public function delete(CoreJoint $joint): void
    {
        DB::transaction(function () use ($joint) {
            $snapshot = $joint->only(['joint_type', 'target_node_id', 'odc_id', 'odp_id']);
            $joint->delete();
            $this->resyncAfterDelete($snapshot);
        });
    }

    /** @param array<string, mixed> $data */
    private function normalizeJointData(array $data, ?CoreJoint $existing = null): array
    {
        unset($data['gps_coordinates']);

        $jointType = CoreJointType::from($data['joint_type'] ?? $existing?->joint_type?->value ?? CoreJointType::OtbToOdc->value);
        $sourceNodeId = (int) ($data['source_node_id'] ?? $existing?->source_node_id);
        $targetNodeId = (int) ($data['target_node_id'] ?? $existing?->target_node_id);
        $coreNumber = (int) ($data['core_number'] ?? $existing?->core_number ?? 0);

        $this->validateHierarchy($jointType, $sourceNodeId, $targetNodeId);

        if (empty($data['fiber_core_a_id']) || empty($data['fiber_core_b_id'])) {
            if ($coreNumber > 0) {
                $pair = $this->findCorePairAtNodes($sourceNodeId, $targetNodeId, $coreNumber);

                if (! $pair) {
                    throw new InvalidArgumentException(__('hfnms.joint_core_not_found'));
                }

                $data['fiber_core_a_id'] = $pair['source']->id;
                $data['fiber_core_b_id'] = $pair['target']->id;
                $data['core_number'] = $coreNumber;
            }
        } else {
            $coreA = FiberCore::query()->find((int) $data['fiber_core_a_id']);
            $coreB = FiberCore::query()->find((int) $data['fiber_core_b_id']);

            if (! $coreA || ! $coreB) {
                throw new InvalidArgumentException(__('hfnms.joint_core_required'));
            }

            $this->validateExplicitCoresAtNodes($coreA, $coreB, $sourceNodeId, $targetNodeId);

            $data['core_number'] = $coreNumber > 0
                ? $coreNumber
                : (int) $coreA->core_number;
        }

        $data['joint_type'] = $jointType->value;
        $data['source_node_id'] = $sourceNodeId;
        $data['target_node_id'] = $targetNodeId;
        $data['network_node_id'] = $targetNodeId;

        if ($jointType === CoreJointType::OtbToOdc) {
            $data['odc_id'] = Odc::query()->where('network_node_id', $targetNodeId)->value('id');
            $data['odp_id'] = null;
        } elseif ($jointType === CoreJointType::OdcToOdp) {
            $data['odp_id'] = Odp::query()->where('network_node_id', $targetNodeId)->value('id');
            $data['odc_id'] = Odc::query()->where('network_node_id', $sourceNodeId)->value('id');
        } else {
            $data['odp_id'] = null;
            $data['odc_id'] = Odc::query()->where('network_node_id', $sourceNodeId)->value('id');
        }

        if (empty($data['latitude']) || empty($data['longitude'])) {
            $node = NetworkNode::query()->find($targetNodeId);
            if ($node) {
                $data['latitude'] = $node->latitude;
                $data['longitude'] = $node->longitude;
            }
        }

        return $data;
    }

    /** @param array<string, mixed> $data */
    private function validateCorePair(array $data, ?int $ignoreJointId = null): void
    {
        $coreAId = (int) ($data['fiber_core_a_id'] ?? 0);
        $coreBId = (int) ($data['fiber_core_b_id'] ?? 0);
        $coreNumber = (int) ($data['core_number'] ?? 0);

        if (! $coreAId || ! $coreBId) {
            throw new InvalidArgumentException(__('hfnms.joint_core_required'));
        }

        $coreA = FiberCore::query()->with('cable')->findOrFail($coreAId);
        $coreB = FiberCore::query()->findOrFail($coreBId);

        if ($coreAId === $coreBId) {
            $sourceNodeId = (int) ($data['source_node_id'] ?? 0);
            $targetNodeId = (int) ($data['target_node_id'] ?? 0);
            $cable = $coreA->cable;

            $endpoints = $cable ? [(int) $cable->start_node_id, (int) $cable->end_node_id] : [];
            $isThroughCable = $cable
                && in_array($sourceNodeId, $endpoints, true)
                && in_array($targetNodeId, $endpoints, true);

            if (! $isThroughCable) {
                throw new InvalidArgumentException(__('hfnms.joint_same_core'));
            }
        }

        $differentCables = (int) $coreA->cable_id !== (int) $coreB->cable_id;

        if (! $differentCables && $coreA->core_number !== $coreB->core_number) {
            throw new InvalidArgumentException(__('hfnms.joint_core_number_mismatch', [
                'a' => $coreA->core_number,
                'b' => $coreB->core_number,
            ]));
        }

        if ($coreNumber > 0 && $coreA->core_number !== $coreNumber) {
            throw new InvalidArgumentException(__('hfnms.joint_core_number_invalid'));
        }

        $exists = CoreJoint::query()
            ->when($ignoreJointId, fn ($q) => $q->where('id', '!=', $ignoreJointId))
            ->where('status', 'active')
            ->where(function ($query) use ($coreAId, $coreBId, $coreNumber) {
                $query->where('core_number', $coreNumber)
                    ->orWhere(function ($q) use ($coreAId, $coreBId) {
                        $q->where('fiber_core_a_id', $coreAId)->where('fiber_core_b_id', $coreBId);
                    })
                    ->orWhere(function ($q) use ($coreAId, $coreBId) {
                        $q->where('fiber_core_a_id', $coreBId)->where('fiber_core_b_id', $coreAId);
                    });
            })
            ->exists();

        if ($exists) {
            throw new InvalidArgumentException(__('hfnms.joint_already_exists'));
        }
    }

    private function validateHierarchy(CoreJointType $jointType, int $sourceNodeId, int $targetNodeId): void
    {
        $source = NetworkNode::query()->find($sourceNodeId);
        $target = NetworkNode::query()->find($targetNodeId);

        if (! $source || $source->type !== $jointType->sourceType()) {
            throw new InvalidArgumentException(__('hfnms.joint_invalid_source', ['type' => $jointType->sourceType()->label()]));
        }

        if (! $target || $target->type !== $jointType->targetType()) {
            throw new InvalidArgumentException(__('hfnms.joint_invalid_target', ['type' => $jointType->targetType()->label()]));
        }

        if ($jointType === CoreJointType::OdcToOdc) {
            if ($source->id === $target->id || (int) $source->parent_id !== (int) $target->parent_id) {
                throw new InvalidArgumentException(__('hfnms.joint_hierarchy_invalid'));
            }

            return;
        }

        if ((int) $target->parent_id !== (int) $source->id) {
            throw new InvalidArgumentException(__('hfnms.joint_hierarchy_invalid'));
        }
    }

    private function validateExplicitCoresAtNodes(
        FiberCore $coreA,
        FiberCore $coreB,
        int $sourceNodeId,
        int $targetNodeId,
    ): void {
        $cableA = $coreA->cable ?? FiberCable::query()->find($coreA->cable_id);
        $cableB = $coreB->cable ?? FiberCable::query()->find($coreB->cable_id);

        if (! $cableA || ! $cableB) {
            throw new InvalidArgumentException(__('hfnms.joint_core_not_found'));
        }

        $endpointsA = [(int) $cableA->start_node_id, (int) $cableA->end_node_id];
        $endpointsB = [(int) $cableB->start_node_id, (int) $cableB->end_node_id];

        if (! in_array($sourceNodeId, $endpointsA, true)) {
            throw new InvalidArgumentException(__('hfnms.joint_source_core_not_at_node'));
        }

        if (! in_array($targetNodeId, $endpointsB, true)) {
            throw new InvalidArgumentException(__('hfnms.joint_target_core_not_at_node'));
        }
    }

    /** @return SupportCollection<int, int> */
    private function cableIdsAtNode(int $nodeId): SupportCollection
    {
        return FiberCable::query()
            ->where(function ($query) use ($nodeId) {
                $query->where('start_node_id', $nodeId)
                    ->orWhere('end_node_id', $nodeId);
            })
            ->pluck('id');
    }

    /** @return list<int> */
    private function joinableCoreNumbers(int $sourceNodeId, int $targetNodeId): array
    {
        $sourceNumbers = $this->coresAtNode($sourceNodeId)->pluck('core_number')->unique();
        $targetNumbers = $this->coresAtNode($targetNodeId)->pluck('core_number')->unique();

        return $sourceNumbers
            ->intersect($targetNumbers)
            ->filter(fn (int $num) => $this->findCorePairAtNodes($sourceNodeId, $targetNodeId, $num) !== null)
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return array{source: FiberCore, target: FiberCore}|null
     */
    private function findCorePairAtNodes(int $sourceNodeId, int $targetNodeId, int $coreNumber): ?array
    {
        $sourceCores = $this->coresAtNode($sourceNodeId)
            ->where('core_number', $coreNumber)
            ->values();
        $targetCores = $this->coresAtNode($targetNodeId)
            ->where('core_number', $coreNumber)
            ->values();

        $preferredPairs = [];
        $fallbackPairs = [];

        foreach ($sourceCores as $sourceCore) {
            foreach ($targetCores as $targetCore) {
                if ($sourceCore->id === $targetCore->id) {
                    continue;
                }

                $pair = ['source' => $sourceCore, 'target' => $targetCore];

                if ((int) $sourceCore->cable_id !== (int) $targetCore->cable_id) {
                    $preferredPairs[] = $pair;
                } else {
                    $fallbackPairs[] = $pair;
                }
            }
        }

        return $preferredPairs[0] ?? $fallbackPairs[0] ?? null;
    }

    /** @return SupportCollection<int, FiberCore> */
    private function coresAtNode(int $nodeId): SupportCollection
    {
        $cableIds = $this->cableIdsAtNode($nodeId);

        if ($cableIds->isEmpty()) {
            return collect();
        }

        return FiberCore::query()
            ->with('cable:id,code')
            ->whereIn('cable_id', $cableIds)
            ->orderBy('core_number')
            ->get();
    }

    private function findCoreAtNode(int $nodeId, int $coreNumber): ?FiberCore
    {
        return $this->coresAtNode($nodeId)->firstWhere('core_number', $coreNumber);
    }

    /** @return SupportCollection<int, int> */
    private function usedCoreNumbers(
        CoreJointType $jointType,
        int $sourceNodeId,
        int $targetNodeId,
        ?int $exceptJointId = null,
    ): SupportCollection {
        return CoreJoint::query()
            ->when($exceptJointId, fn ($q) => $q->where('id', '!=', $exceptJointId))
            ->where('status', 'active')
            ->where('joint_type', $jointType->value)
            ->where('source_node_id', $sourceNodeId)
            ->where('target_node_id', $targetNodeId)
            ->whereNotNull('core_number')
            ->pluck('core_number');
    }

    private function markCoresSpliced(CoreJoint $joint): void
    {
        foreach ([$joint->coreA, $joint->coreB] as $core) {
            if (! $core) {
                continue;
            }

            $core->update([
                'status' => CoreStatus::Used,
                'loss_db' => $joint->splice_loss_db ?? $core->loss_db,
                'metadata' => array_merge($core->metadata ?? [], [
                    'joint_id' => $joint->id,
                    'joint_code' => $joint->code,
                    'joint_type' => $joint->joint_type?->value,
                    'core_number' => $joint->core_number,
                ]),
            ]);
        }
    }

    private function syncAssetCoreUsage(CoreJoint $joint): void
    {
        if ($joint->joint_type === CoreJointType::OtbToOdc && $joint->odc_id) {
            $count = CoreJoint::query()
                ->where('odc_id', $joint->odc_id)
                ->where('joint_type', CoreJointType::OtbToOdc->value)
                ->where('status', 'active')
                ->pluck('core_number')
                ->unique()
                ->count();

            Odc::query()->where('id', $joint->odc_id)->update(['cores_from_otb' => $count]);
        }

        if ($joint->joint_type === CoreJointType::OdcToOdp && $joint->odp_id) {
            $count = CoreJoint::query()
                ->where('odp_id', $joint->odp_id)
                ->where('joint_type', CoreJointType::OdcToOdp->value)
                ->where('status', 'active')
                ->pluck('core_number')
                ->unique()
                ->count();

            Odp::query()->where('id', $joint->odp_id)->update(['cores_from_odc' => $count]);
        }

        if (in_array($joint->joint_type, [CoreJointType::OdcToOdp, CoreJointType::OdcToOdc], true) && $joint->odc_id) {
            $odcCount = CoreJoint::query()
                ->where('odc_id', $joint->odc_id)
                ->whereIn('joint_type', [CoreJointType::OdcToOdp->value, CoreJointType::OdcToOdc->value])
                ->where('status', 'active')
                ->pluck('core_number')
                ->unique()
                ->count();

            Odc::query()->where('id', $joint->odc_id)->update(['cores_to_odp' => $odcCount]);
        }
    }

    /** @param array<string, mixed> $snapshot */
    private function resyncAfterDelete(array $snapshot): void
    {
        if (! empty($snapshot['odc_id'])) {
            $odcId = (int) $snapshot['odc_id'];
            Odc::query()->where('id', $odcId)->update([
                'cores_from_otb' => CoreJoint::query()
                    ->where('odc_id', $odcId)
                    ->where('joint_type', CoreJointType::OtbToOdc->value)
                    ->where('status', 'active')
                    ->pluck('core_number')->unique()->count(),
                'cores_to_odp' => CoreJoint::query()
                    ->where('odc_id', $odcId)
                    ->where('joint_type', CoreJointType::OdcToOdp->value)
                    ->where('status', 'active')
                    ->pluck('core_number')->unique()->count(),
            ]);
        }

        if (! empty($snapshot['odp_id'])) {
            $odpId = (int) $snapshot['odp_id'];
            Odp::query()->where('id', $odpId)->update([
                'cores_from_odc' => CoreJoint::query()
                    ->where('odp_id', $odpId)
                    ->where('joint_type', CoreJointType::OdcToOdp->value)
                    ->where('status', 'active')
                    ->pluck('core_number')->unique()->count(),
            ]);
        }
    }
}
