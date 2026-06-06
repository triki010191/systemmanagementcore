<?php

namespace App\Services\Network;

use App\Models\FiberCable;
use App\Models\FiberCore;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

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

    /** @return Collection<int, FiberCable> */
    public function cableOptions(): Collection
    {
        return FiberCable::query()
            ->orderBy('code')
            ->get(['id', 'code', 'name']);
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
