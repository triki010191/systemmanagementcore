<?php

namespace App\Services\Network;

use App\Models\Customer;
use App\Models\FiberCore;
use App\Models\NetworkNode;
use Illuminate\Support\Collection;

class ImpactAnalysisService
{
    public function affectedCustomersByCore(int $coreId): Collection
    {
        $core = FiberCore::query()->findOrFail($coreId);

        $direct = Customer::query()
            ->with(['networkNode', 'connection.odp'])
            ->whereHas('connection', fn ($q) => $q->where('cable_core_id', $coreId))
            ->get();

        $viaLinks = Customer::query()
            ->with(['networkNode', 'connection.odp'])
            ->whereHas('networkNode.incomingLinks', fn ($q) => $q
                ->where('fiber_core_id', $coreId)
                ->where('status', 'active'))
            ->get();

        return $direct->merge($viaLinks)->unique('id')->values();
    }

    public function affectedCustomersByNode(int $nodeId): Collection
    {
        NetworkNode::query()->findOrFail($nodeId);

        $nodeIds = $this->descendantNodeIds($nodeId);

        return Customer::query()
            ->with(['networkNode', 'connection.odp.networkNode'])
            ->where(function ($q) use ($nodeIds) {
                $q->whereIn('network_node_id', $nodeIds)
                    ->orWhereHas('connection.odp', fn ($q2) => $q2->whereIn('network_node_id', $nodeIds));
            })
            ->get();
    }

    public function impactSummaryByNode(int $nodeId): array
    {
        $customers = $this->affectedCustomersByNode($nodeId);
        $node = NetworkNode::query()->findOrFail($nodeId);

        return [
            'target_type' => 'node',
            'node' => $node,
            'core' => null,
            'affected_customer_count' => $customers->count(),
            'customers' => $customers,
            'active_count' => $customers->where('status', 'active')->count(),
            'suspended_count' => $customers->where('status', 'suspended')->count(),
        ];
    }

    public function impactSummaryByCore(int $coreId): array
    {
        $core = FiberCore::query()->with('cable')->findOrFail($coreId);
        $customers = $this->affectedCustomersByCore($coreId);

        return [
            'target_type' => 'core',
            'node' => null,
            'core' => $core,
            'affected_customer_count' => $customers->count(),
            'customers' => $customers,
            'active_count' => $customers->where('status', 'active')->count(),
            'suspended_count' => $customers->where('status', 'suspended')->count(),
        ];
    }

    /** @return list<int> */
    private function descendantNodeIds(int $nodeId): array
    {
        $ids = [$nodeId];

        $children = NetworkNode::query()
            ->where('parent_id', $nodeId)
            ->pluck('id');

        foreach ($children as $childId) {
            $ids = array_merge($ids, $this->descendantNodeIds($childId));
        }

        return $ids;
    }
}
