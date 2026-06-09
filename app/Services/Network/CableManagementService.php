<?php

namespace App\Services\Network;

use App\Models\FiberCable;
use App\Models\FiberCore;
use App\Models\NetworkLink;
use App\Models\NetworkNode;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CableManagementService
{
    public function __construct(
        private readonly CableProvisioningService $provisioningService,
        private readonly QrCodeService $qrCodeService,
        private readonly RoadRoutingService $roadRoutingService,
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return FiberCable::query()
            ->with(['startNode', 'endNode'])
            ->withCount('cores')
            ->latest()
            ->paginate($perPage);
    }

    /** @return Collection<int, NetworkNode> */
    public function nodeOptions(): Collection
    {
        return NetworkNode::query()
            ->orderBy('type')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type', 'latitude', 'longitude']);
    }

    public function find(int $id): FiberCable
    {
        return FiberCable::query()
            ->with(['startNode', 'endNode', 'tubes.tubeColor', 'cores.tubeColor'])
            ->findOrFail($id);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): FiberCable
    {
        return DB::transaction(function () use ($data) {
            $data = $this->normalizeCableData($data);
            $data['status'] = $data['status'] ?? 'planned';

            $cable = FiberCable::query()->create($data);
            $this->provisioningService->provision($cable);

            return $cable->fresh(['startNode', 'endNode']);
        });
    }

    /** @param array<string, mixed> $data */
    public function update(FiberCable $cable, array $data): FiberCable
    {
        $coreCountChanged = isset($data['core_count']) && (int) $data['core_count'] !== (int) $cable->core_count;

        if ($coreCountChanged && $cable->cores()->where('status', '!=', 'available')->exists()) {
            throw new InvalidArgumentException(__('hfnms.cable_core_count_locked'));
        }

        return DB::transaction(function () use ($cable, $data, $coreCountChanged) {
            $data = $this->normalizeCableData($data, $cable);

            $cable->update($data);

            if ($coreCountChanged) {
                $cable->cores()->delete();
                $cable->tubes()->delete();
                $this->provisioningService->provision($cable->fresh());
            } else {
                $this->qrCodeService->generateForCable($cable->fresh());
            }

            return $cable->fresh(['startNode', 'endNode']);
        });
    }

    public function delete(FiberCable $cable): void
    {
        DB::transaction(function () use ($cable) {
            if (NetworkLink::query()->where('cable_id', $cable->id)->exists()) {
                throw new InvalidArgumentException(__('hfnms.cable_delete_has_links'));
            }

            if (FiberCore::query()->where('cable_id', $cable->id)->where('status', '!=', 'available')->exists()) {
                throw new InvalidArgumentException(__('hfnms.cable_delete_has_used_cores'));
            }

            $cable->cores()->delete();
            $cable->tubes()->delete();
            $cable->delete();
        });
    }

    /** @param array<string, mixed> $data */
    private function normalizeCableData(array $data, ?FiberCable $existing = null): array
    {
        $coreCount = (int) ($data['core_count'] ?? $existing?->core_count ?? 12);
        $data['tube_count'] = (int) ($data['tube_count'] ?? max(1, (int) ceil($coreCount / 12)));

        foreach (['start_node_id', 'end_node_id'] as $field) {
            if (array_key_exists($field, $data) && ($data[$field] === '' || $data[$field] === null)) {
                $data[$field] = null;
            }
        }

        return $this->maybeSnapRouteToRoads($data, $existing);
    }

    /** @param array<string, mixed> $data */
    private function maybeSnapRouteToRoads(array $data, ?FiberCable $existing = null): array
    {
        $snapToRoads = (bool) ($data['snap_to_roads'] ?? false);
        unset($data['snap_to_roads']);

        if (! $snapToRoads || array_key_exists('route_geometry', $data)) {
            return $data;
        }

        $startNodeId = $data['start_node_id'] ?? $existing?->start_node_id;
        $endNodeId = $data['end_node_id'] ?? $existing?->end_node_id;

        if (! $startNodeId || ! $endNodeId) {
            return $data;
        }

        $nodes = NetworkNode::query()
            ->whereIn('id', [$startNodeId, $endNodeId])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get(['id', 'latitude', 'longitude'])
            ->keyBy('id');

        $start = $nodes->get($startNodeId);
        $end = $nodes->get($endNodeId);

        if (! $start || ! $end) {
            return $data;
        }

        $geometry = $this->roadRoutingService->routeBetween(
            (float) $start->latitude,
            (float) $start->longitude,
            (float) $end->latitude,
            (float) $end->longitude,
        );

        if ($geometry !== null) {
            $data['route_geometry'] = $geometry;

            if (empty($data['length_meters'])) {
                $data['length_meters'] = $this->estimatePathLengthMeters($geometry);
            }
        }

        return $data;
    }

    /** @param list<array{lat: float, lng: float}> $path */
    private function estimatePathLengthMeters(array $path): float
    {
        $total = 0.0;

        for ($index = 1; $index < count($path); $index++) {
            $total += $this->haversineMeters(
                $path[$index - 1]['lat'],
                $path[$index - 1]['lng'],
                $path[$index]['lat'],
                $path[$index]['lng'],
            );
        }

        return round($total, 2);
    }

    private function haversineMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
