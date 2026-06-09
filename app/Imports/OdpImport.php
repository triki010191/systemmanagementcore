<?php

namespace App\Imports;

use App\Enums\NetworkNodeType;
use App\Models\NetworkNode;
use App\Services\Network\AssetCodeGenerator;
use App\Services\Network\NetworkAssetService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use RuntimeException;

class OdpImport implements ToCollection, WithHeadingRow
{
    /** @var list<string> */
    public array $errors = [];

    public int $imported = 0;

    public function __construct(
        private readonly NetworkAssetService $assetService,
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

                $parentCode = trim((string) ($row['parent_odc_code'] ?? $row['parent_splitter_code'] ?? ''));
                if ($parentCode === '') {
                    throw new RuntimeException(__('hfnms.import_parent_required'));
                }

                $parent = NetworkNode::query()
                    ->where('code', $parentCode)
                    ->where('type', NetworkNodeType::Odc)
                    ->first();

                if (! $parent) {
                    throw new RuntimeException(__('hfnms.import_parent_not_found', ['code' => $parentCode]));
                }

                $code = trim((string) ($row['code'] ?? ''));
                if ($code === '') {
                    $code = $this->codeGenerator->next(NetworkNodeType::Odp);
                }

                $this->assetService->create(NetworkNodeType::Odp, [
                    'code' => $code,
                    'name' => trim((string) ($row['name'] ?? $code)),
                    'latitude' => (float) $row['latitude'],
                    'longitude' => (float) $row['longitude'],
                    'address' => trim((string) ($row['address'] ?? '')) ?: null,
                    'parent_id' => $parent->id,
                    'status' => 'active',
                    'port_capacity' => (int) ($row['port_capacity'] ?? 16),
                    'port_used' => 0,
                    'cores_from_odc' => (int) ($row['cores_from_odc'] ?? 0),
                ]);

                $this->imported++;
            } catch (\Throwable $e) {
                $this->errors[] = "Baris {$line}: {$e->getMessage()}";
            }
        }
    }

    private function isEmptyRow(Collection $row): bool
    {
        return trim((string) ($row['name'] ?? '')) === ''
            && trim((string) ($row['parent_odc_code'] ?? $row['parent_splitter_code'] ?? '')) === '';
    }
}
