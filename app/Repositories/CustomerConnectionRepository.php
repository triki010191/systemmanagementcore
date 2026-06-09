<?php

namespace App\Repositories;

use App\Models\CustomerConnection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CustomerConnectionRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return CustomerConnection::query()
            ->with([
                'customer.networkNode',
                'odp.networkNode',
                'cableCore.cable',
            ])
            ->latest()
            ->paginate($perPage);
    }

    public function find(int $id): CustomerConnection
    {
        return CustomerConnection::query()
            ->with([
                'customer.networkNode.parent',
                'odp.networkNode.parent',
                'cableCore.cable',
            ])
            ->findOrFail($id);
    }

    public function create(array $attributes): CustomerConnection
    {
        return CustomerConnection::query()->create($attributes);
    }

    public function update(CustomerConnection $connection, array $attributes): CustomerConnection
    {
        $connection->update($attributes);

        return $connection->fresh();
    }

    public function delete(CustomerConnection $connection): void
    {
        $connection->delete();
    }

    public function odpPortTaken(int $odpId, int $portNumber, ?int $exceptConnectionId = null): bool
    {
        $query = CustomerConnection::query()
            ->where('odp_id', $odpId)
            ->where('odp_port_number', $portNumber);

        if ($exceptConnectionId) {
            $query->where('id', '!=', $exceptConnectionId);
        }

        return $query->exists();
    }

    public function coreTaken(int $coreId, ?int $exceptConnectionId = null): bool
    {
        $query = CustomerConnection::query()->where('cable_core_id', $coreId);

        if ($exceptConnectionId) {
            $query->where('id', '!=', $exceptConnectionId);
        }

        return $query->exists();
    }
}
