<?php

namespace App\Services\Network;

use App\Enums\CoreStatus;
use App\Models\Customer;
use App\Models\CustomerConnection;
use App\Models\FiberCore;
use App\Models\Odp;
use App\Repositories\CustomerConnectionRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CustomerConnectionService
{
    public function __construct(
        private readonly CustomerConnectionRepository $repository,
        private readonly PathTracingService $pathTracingService,
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    public function find(int $id): CustomerConnection
    {
        return $this->repository->find($id);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): CustomerConnection
    {
        return DB::transaction(function () use ($data) {
            $customer = Customer::query()->with('networkNode')->findOrFail($data['customer_id']);

            if ($customer->connection) {
                throw new RuntimeException(__('hfnms.connection_already_exists'));
            }

            $this->validateAssignment($data);

            $connection = $this->repository->create([
                'customer_id' => $customer->id,
                'odp_id' => $data['odp_id'],
                'odp_port_number' => $data['odp_port_number'],
                'cable_core_id' => $data['cable_core_id'] ?? null,
                'drop_length_m' => $data['drop_length_m'] ?? null,
                'connected_at' => $data['connected_at'] ?? now()->toDateString(),
            ]);

            $this->applySideEffects($connection, null);

            return $this->repository->find($connection->id);
        });
    }

    /** @param array<string, mixed> $data */
    public function update(CustomerConnection $connection, array $data): CustomerConnection
    {
        return DB::transaction(function () use ($connection, $data) {
            $previous = $connection->only([
                'odp_id', 'odp_port_number', 'cable_core_id',
            ]);

            $merged = array_merge($previous, array_filter([
                'odp_id' => $data['odp_id'] ?? null,
                'odp_port_number' => $data['odp_port_number'] ?? null,
                'cable_core_id' => $data['cable_core_id'] ?? null,
            ], fn ($v) => $v !== null));

            $this->validateAssignment(array_merge($merged, [
                'customer_id' => $connection->customer_id,
            ]), $connection->id);

            $this->repository->update($connection, [
                'odp_id' => $data['odp_id'] ?? $connection->odp_id,
                'odp_port_number' => $data['odp_port_number'] ?? $connection->odp_port_number,
                'cable_core_id' => $data['cable_core_id'] ?? $connection->cable_core_id,
                'drop_length_m' => $data['drop_length_m'] ?? $connection->drop_length_m,
                'connected_at' => $data['connected_at'] ?? $connection->connected_at,
            ]);

            $this->revertSideEffects($previous);
            $this->applySideEffects($connection->fresh(), null);

            return $this->repository->find($connection->id);
        });
    }

    public function delete(CustomerConnection $connection): void
    {
        DB::transaction(function () use ($connection) {
            $snapshot = $connection->only([
                'odp_id', 'odp_port_number', 'cable_core_id',
            ]);

            $this->repository->delete($connection);
            $this->revertSideEffects($snapshot);
        });
    }

    public function pathIsComplete(CustomerConnection $connection): bool
    {
        return $this->pathTracingService->validatePathCompleteness($connection->customer_id);
    }

    /** @return Collection<int, Customer> */
    public function customersWithoutConnection(): Collection
    {
        return Customer::query()
            ->with('networkNode.parent')
            ->whereDoesntHave('connection')
            ->orderBy('code')
            ->get();
    }

    /** @return Collection<int, Odp> */
    public function availableOdps(): Collection
    {
        return Odp::query()
            ->with('networkNode')
            ->orderBy('code')
            ->get();
    }

    /** @return array{cable_cores: Collection, taken_odp_ports: Collection, odp: array<string, mixed>|null} */
    public function optionsForOdp(int $odpId, ?int $exceptConnectionId = null): array
    {
        $odp = Odp::query()->with('networkNode.parent')->findOrFail($odpId);

        $usedCoreIds = CustomerConnection::query()
            ->when($exceptConnectionId, fn ($q) => $q->where('id', '!=', $exceptConnectionId))
            ->whereNotNull('cable_core_id')
            ->pluck('cable_core_id');

        $cableCores = FiberCore::query()
            ->with('cable')
            ->whereIn('status', [CoreStatus::Available, CoreStatus::Used])
            ->orderBy('cable_id')
            ->orderBy('core_number')
            ->get()
            ->map(fn (FiberCore $core) => [
                'id' => $core->id,
                'label' => sprintf(
                    '%s — Core %d (Tube %d)',
                    $core->cable?->code ?? 'Cable',
                    $core->core_number,
                    $core->tube_number,
                ),
                'status' => $core->status->value,
                'available' => ! $usedCoreIds->contains($core->id)
                    || in_array($core->status, [CoreStatus::Available], true),
            ]);

        $takenOdpPorts = CustomerConnection::query()
            ->where('odp_id', $odpId)
            ->when($exceptConnectionId, fn ($q) => $q->where('id', '!=', $exceptConnectionId))
            ->pluck('odp_port_number')
            ->filter()
            ->values();

        return [
            'odp' => [
                'id' => $odp->id,
                'code' => $odp->code,
                'port_capacity' => $odp->port_capacity,
                'port_used' => $odp->port_used,
            ],
            'cable_cores' => $cableCores,
            'taken_odp_ports' => $takenOdpPorts,
        ];
    }

    /** @param array<string, mixed> $data */
    private function validateAssignment(array $data, ?int $exceptConnectionId = null): void
    {
        $odp = Odp::query()->findOrFail($data['odp_id']);

        if ($this->repository->odpPortTaken($odp->id, (int) $data['odp_port_number'], $exceptConnectionId)) {
            throw new RuntimeException(__('hfnms.odp_port_taken'));
        }

        if ($odp->port_capacity > 0 && (int) $data['odp_port_number'] > $odp->port_capacity) {
            throw new RuntimeException(__('hfnms.odp_port_exceeds_capacity'));
        }

        if (! empty($data['cable_core_id'])) {
            $core = FiberCore::query()->findOrFail($data['cable_core_id']);

            if ($core->status === CoreStatus::Broken) {
                throw new RuntimeException(__('hfnms.core_is_broken'));
            }

            if ($this->repository->coreTaken($core->id, $exceptConnectionId)) {
                throw new RuntimeException(__('hfnms.core_already_used'));
            }
        }
    }

    /** @param array<string, mixed>|null $previous */
    private function applySideEffects(CustomerConnection $connection, ?array $previous): void
    {
        $connection->load(['customer.networkNode', 'odp.networkNode', 'cableCore']);

        $connection->customer->networkNode?->update([
            'parent_id' => $connection->odp->network_node_id,
        ]);

        if ($connection->cable_core_id) {
            FiberCore::query()
                ->where('id', $connection->cable_core_id)
                ->update([
                    'status' => CoreStatus::Used,
                    'target_node_id' => $connection->customer->network_node_id,
                ]);
        }

        $this->syncOdpPortUsed($connection->odp_id);
    }

    /** @param array<string, mixed> $snapshot */
    private function revertSideEffects(array $snapshot): void
    {
        if (! empty($snapshot['cable_core_id'])) {
            $stillUsed = CustomerConnection::query()
                ->where('cable_core_id', $snapshot['cable_core_id'])
                ->exists();

            if (! $stillUsed) {
                FiberCore::query()
                    ->where('id', $snapshot['cable_core_id'])
                    ->update([
                        'status' => CoreStatus::Available,
                        'target_node_id' => null,
                    ]);
            }
        }

        if (! empty($snapshot['odp_id'])) {
            $this->syncOdpPortUsed($snapshot['odp_id']);
        }
    }

    private function syncOdpPortUsed(int $odpId): void
    {
        Odp::query()
            ->where('id', $odpId)
            ->update(['port_used' => CustomerConnection::query()->where('odp_id', $odpId)->count()]);
    }
}
