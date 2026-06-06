<?php

namespace App\Repositories;

use App\Enums\NetworkNodeType;
use App\Models\NetworkNode;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class NetworkNodeRepository
{
    public function find(int $id): ?NetworkNode
    {
        return NetworkNode::query()->find($id);
    }

    public function findByCode(string $code): ?NetworkNode
    {
        return NetworkNode::query()->where('code', $code)->first();
    }

    public function create(array $attributes): NetworkNode
    {
        return NetworkNode::query()->create($attributes);
    }

    public function update(NetworkNode $node, array $attributes): NetworkNode
    {
        $node->update($attributes);

        return $node->fresh();
    }

    public function softDelete(NetworkNode $node): void
    {
        $node->delete();
    }

    public function hasActiveChildren(NetworkNode $node): bool
    {
        return $node->children()->exists();
    }

    public function hasActiveLinks(NetworkNode $node): bool
    {
        return $node->outgoingLinks()->where('status', 'active')->exists()
            || $node->incomingLinks()->where('status', 'active')->exists();
    }

    /** @return Collection<int, NetworkNode> */
    public function parentsOfType(NetworkNodeType $type): Collection
    {
        return NetworkNode::query()
            ->where('type', $type)
            ->where('status', '!=', 'inactive')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type']);
    }

    public function paginateByType(NetworkNodeType $type, int $perPage = 15): LengthAwarePaginator
    {
        return NetworkNode::query()
            ->where('type', $type)
            ->with('parent')
            ->latest()
            ->paginate($perPage);
    }
}
