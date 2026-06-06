<?php

namespace App\Imports;

use App\Enums\NetworkNodeType;
use App\Models\Odp;
use App\Services\Network\AssetCodeGenerator;
use App\Services\Network\CustomerConnectionService;
use App\Services\Network\NetworkAssetService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use RuntimeException;

class CustomerImport implements ToCollection, WithHeadingRow
{
    /** @var list<string> */
    public array $errors = [];

    public int $imported = 0;

    public int $connectionsCreated = 0;

    public function __construct(
        private readonly NetworkAssetService $assetService,
        private readonly CustomerConnectionService $connectionService,
        private readonly AssetCodeGenerator $codeGenerator,
    ) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $line = $index + 2;

            try {
                if ($this->isEmptyRow($row)) {
                    continue;
                }

                $odpCode = trim((string) ($row['odp_code'] ?? ''));
                $odp = $odpCode !== ''
                    ? Odp::query()->where('code', $odpCode)->first()
                    : null;

                if ($odpCode !== '' && ! $odp) {
                    throw new RuntimeException(__('hfnms.import_odp_not_found', ['code' => $odpCode]));
                }

                $code = trim((string) ($row['code'] ?? ''));
                if ($code === '') {
                    $code = $this->codeGenerator->next(NetworkNodeType::Customer);
                }

                $customer = $this->assetService->create(NetworkNodeType::Customer, [
                    'code' => $code,
                    'name' => trim((string) ($row['name'] ?? $code)),
                    'latitude' => (float) $row['latitude'],
                    'longitude' => (float) $row['longitude'],
                    'address' => trim((string) ($row['address'] ?? '')) ?: null,
                    'parent_id' => $odp?->network_node_id,
                    'status' => 'active',
                    'phone' => trim((string) ($row['phone'] ?? '')) ?: null,
                    'email' => trim((string) ($row['email'] ?? '')) ?: null,
                    'service_type' => in_array($row['service_type'] ?? 'home', ['home', 'business'], true)
                        ? $row['service_type']
                        : 'home',
                    'onu_serial' => trim((string) ($row['onu_serial'] ?? '')) ?: null,
                    'customer_status' => 'active',
                ]);

                $this->imported++;

                $odpPort = $row['odp_port'] ?? null;
                if ($odp && $odpPort) {
                    $this->connectionService->create([
                        'customer_id' => $customer->id,
                        'odp_id' => $odp->id,
                        'odp_port_number' => (int) $odpPort,
                        'drop_length_m' => isset($row['drop_length_m']) ? (float) $row['drop_length_m'] : null,
                        'connected_at' => now()->toDateString(),
                    ]);
                    $this->connectionsCreated++;
                }
            } catch (\Throwable $e) {
                $this->errors[] = "Baris {$line}: {$e->getMessage()}";
            }
        }
    }

    private function isEmptyRow(Collection $row): bool
    {
        return trim((string) ($row['name'] ?? '')) === '';
    }
}
