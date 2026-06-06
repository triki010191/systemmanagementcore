<?php

namespace App\Services\Network;

use App\DTO\PathTraceResult;
use App\Enums\NetworkNodeType;
use App\Models\Customer;
use Illuminate\Support\Str;
use RuntimeException;

class PathTracingService
{
    /** @var list<NetworkNodeType> */
    private const REQUIRED_HIERARCHY = [
        NetworkNodeType::Pop,
        NetworkNodeType::Olt,
        NetworkNodeType::Otb,
        NetworkNodeType::Odc,
        NetworkNodeType::Splitter,
        NetworkNodeType::Odp,
        NetworkNodeType::Customer,
    ];

    public function traceByCustomerId(int $id): PathTraceResult
    {
        $customer = Customer::query()
            ->with([
                'networkNode',
                'connection.odp.networkNode',
                'connection.cableCore',
                'connection.splitterPort.splitter.networkNode',
            ])
            ->findOrFail($id);

        return $this->buildTrace($customer);
    }

    public function traceByCustomerCode(string $code): PathTraceResult
    {
        $customer = Customer::query()
            ->with([
                'networkNode',
                'connection.odp.networkNode',
                'connection.cableCore',
                'connection.splitterPort.splitter.networkNode',
            ])
            ->where('code', $code)
            ->firstOrFail();

        return $this->buildTrace($customer);
    }

    public function validatePathCompleteness(int $customerId): bool
    {
        return $this->traceByCustomerId($customerId)->isComplete;
    }

    private function buildTrace(Customer $customer): PathTraceResult
    {
        $hops = [];
        $missing = [];

        $customerNode = $customer->networkNode;
        if (! $customerNode) {
            throw new RuntimeException('Customer network node not found.');
        }

        $hops[] = $this->nodeHop($customerNode, $customer);

        $connection = $customer->connection;
        if (! $connection) {
            $missing[] = 'customer_connections';
        } else {
            $odpNode = $connection->odp?->networkNode;
            if ($odpNode) {
                $hops[] = $this->nodeHop($odpNode, null, [
                    'odp_port' => $connection->odp_port_number,
                    'drop_length_m' => $connection->drop_length_m,
                ]);
            } else {
                $missing[] = 'odp';
            }

            if ($connection->splitterPort) {
                $splitterNode = $connection->splitterPort->splitter?->networkNode;
                if ($splitterNode) {
                    $hops[] = $this->nodeHop($splitterNode, null, [
                        'port' => $connection->splitterPort->label ?? "OUT-{$connection->splitterPort->port_number}",
                    ]);
                }
            }

            if ($connection->cableCore) {
                $hops[count($hops) - 1]['core'] = [
                    'number' => $connection->cableCore->core_number,
                    'tube' => $connection->cableCore->tube_number,
                    'loss_db' => $connection->cableCore->loss_db,
                ];
            }
        }

        $upstream = $this->traverseUpstream($customerNode);
        foreach ($upstream as $hop) {
            $hops[] = $hop;
        }

        $foundTypes = collect($hops)->pluck('type')->unique()->values()->all();
        foreach (self::REQUIRED_HIERARCHY as $required) {
            if (! in_array($required->value, $foundTypes, true)) {
                $missing[] = $required->value;
            }
        }

        $hops = collect($hops)
            ->unique('code')
            ->sortBy(fn (array $hop) => NetworkNodeType::from($hop['type'])->hierarchyLevel())
            ->values()
            ->all();

        return new PathTraceResult(
            traceId: (string) Str::uuid(),
            customer: $customer,
            hops: $hops,
            metrics: [
                'rx_power_dbm' => $customer->rx_power_dbm,
                'tx_power_dbm' => $customer->tx_power_dbm,
                'distance_km' => $connection?->drop_length_m
                    ? round((float) $connection->drop_length_m / 1000, 3)
                    : null,
            ],
            isComplete: count($missing) === 0,
            missingSegments: array_values(array_unique($missing)),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function traverseUpstream(\App\Models\NetworkNode $startNode): array
    {
        $hops = [];
        $current = $startNode->parent;

        while ($current) {
            $hops[] = $this->nodeHop($current);
            $current = $current->parent;
        }

        return $hops;
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function nodeHop(
        \App\Models\NetworkNode $node,
        ?Customer $customer = null,
        array $extra = [],
    ): array {
        return array_merge([
            'type' => $node->type->value,
            'type_label' => $node->type->label(),
            'code' => $node->code,
            'name' => $node->name,
            'gps' => [
                'latitude' => $node->latitude,
                'longitude' => $node->longitude,
            ],
            'status' => $node->status->value,
            'customer_code' => $customer?->code,
        ], $extra);
    }
}
