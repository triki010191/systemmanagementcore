<?php

namespace App\Services\Network;

use App\Enums\NetworkNodeStatus;
use App\Enums\NetworkNodeType;
use App\Models\Customer;
use App\Models\NetworkNode;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\Olt;
use App\Models\Otb;
use App\Models\Pop;
use App\Repositories\NetworkNodeRepository;
use App\Services\Audit\AuditLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class NetworkAssetService
{
    public function __construct(
        private readonly NetworkNodeRepository $nodeRepository,
        private readonly AssetCodeGenerator $codeGenerator,
        private readonly QrCodeService $qrCodeService,
        private readonly AuditLogService $auditLog,
    ) {}

    public function paginate(NetworkNodeType $type, int $perPage = 15): LengthAwarePaginator
    {
        $modelClass = $type->domainModelClass();

        return $modelClass::query()
            ->with(['networkNode.parent'])
            ->whereHas('networkNode')
            ->latest()
            ->paginate($perPage);
    }

    public function find(NetworkNodeType $type, int $id): Model
    {
        return $type->domainModelClass()::query()
            ->with(['networkNode.parent', 'networkNode.children'])
            ->when($type === NetworkNodeType::Odp, fn ($query) => $query->with(['odc.networkNode.parent']))
            ->findOrFail($id);
    }

    /** @param array<string, mixed> $data */
    public function create(NetworkNodeType $type, array $data): Model
    {
        return DB::transaction(function () use ($type, $data) {
            $parentNode = $this->resolveParent($type, $data['parent_id'] ?? null);
            $code = $this->resolveCode($type, $data['code'] ?? null);

            $node = $this->nodeRepository->create([
                'uuid' => (string) Str::uuid(),
                'type' => $type,
                'code' => $code,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'latitude' => $data['latitude'] ?? 0,
                'longitude' => $data['longitude'] ?? 0,
                'parent_id' => $parentNode?->id,
                'status' => $data['status'] ?? NetworkNodeStatus::Active,
                'address' => $data['address'] ?? null,
            ]);

            $asset = $this->createDomainRecord($type, $node, $parentNode, $data, $code);

            if ($type->requiresQr()) {
                $this->qrCodeService->generateForNode($node);
            }

            $asset = $asset->fresh(['networkNode.parent']);
            $this->auditLog->log('create', 'network_asset', $node->id, null, $this->auditSnapshot($type, $asset));

            return $asset;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(NetworkNodeType $type, Model $asset, array $data): Model
    {
        return DB::transaction(function () use ($type, $asset, $data) {
            /** @var NetworkNode $node */
            $node = $asset->networkNode;
            $old = $this->auditSnapshot($type, $asset);
            $parentNode = $this->resolveParent($type, $data['parent_id'] ?? $node->parent_id, $node->id);
            $code = $data['code'] ?? $node->code;

            if (! $this->codeGenerator->isUnique($code, $node->id)) {
                throw new RuntimeException(__('hfnms.code_already_used'));
            }

            $this->nodeRepository->update($node, [
                'code' => $code,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'latitude' => array_key_exists('latitude', $data) ? $data['latitude'] : $node->latitude,
                'longitude' => array_key_exists('longitude', $data) ? $data['longitude'] : $node->longitude,
                'parent_id' => $parentNode?->id,
                'status' => isset($data['status'])
                    ? NetworkNodeStatus::from((string) $data['status'])
                    : $node->status,
                'address' => $data['address'] ?? null,
            ]);

            $this->updateDomainRecord($type, $asset, $parentNode, $data, $code);

            $asset = $asset->fresh(['networkNode.parent']);
            $this->auditLog->log('update', 'network_asset', $node->id, $old, $this->auditSnapshot($type, $asset));

            return $asset;
        });
    }

    public function delete(NetworkNodeType $type, Model $asset): void
    {
        /** @var NetworkNode $node */
        $node = $asset->networkNode;

        if ($this->nodeRepository->hasActiveChildren($node)) {
            throw new RuntimeException(__('hfnms.cannot_delete_has_children'));
        }

        if ($this->nodeRepository->hasActiveLinks($node)) {
            throw new RuntimeException(__('hfnms.cannot_delete_has_links'));
        }

        DB::transaction(function () use ($type, $asset, $node) {
            $old = $this->auditSnapshot($type, $asset);
            $nodeId = $node->id;
            $asset->delete();
            $this->nodeRepository->softDelete($node);
            $this->auditLog->log('delete', 'network_asset', $nodeId, $old, null);
        });
    }

    /** @return array<string, mixed> */
    private function auditSnapshot(NetworkNodeType $type, Model $asset): array
    {
        $node = $asset->networkNode;

        return [
            'asset_type' => $type->value,
            'code' => $node?->code,
            'name' => $node?->name,
            'status' => $node?->status?->value ?? $node?->status,
            'parent_id' => $node?->parent_id,
        ];
    }

    private function resolveCode(NetworkNodeType $type, ?string $code): string
    {
        if (! $code && $type->usesAutoCode()) {
            $code = $this->codeGenerator->next($type);
        }

        if (! $code) {
            throw new RuntimeException(__('hfnms.code_required'));
        }

        if (! $this->codeGenerator->isUnique($code)) {
            throw new RuntimeException(__('hfnms.code_already_used'));
        }

        return $code;
    }

    private function resolveParent(NetworkNodeType $type, ?int $parentId, ?int $currentNodeId = null): ?NetworkNode
    {
        $expectedParent = $type->expectedParentType();

        if ($expectedParent === null) {
            return null;
        }

        if (! $parentId) {
            throw new RuntimeException(__('hfnms.parent_required', ['type' => $expectedParent->label()]));
        }

        $parent = $this->nodeRepository->find($parentId);

        if (! $parent || $parent->type !== $expectedParent) {
            throw new RuntimeException(__('hfnms.invalid_parent', ['type' => $expectedParent->label()]));
        }

        if ($currentNodeId && $parentId === $currentNodeId) {
            throw new RuntimeException(__('hfnms.invalid_parent_self'));
        }

        return $parent;
    }

    /** @param array<string, mixed> $data */
    private function createDomainRecord(
        NetworkNodeType $type,
        NetworkNode $node,
        ?NetworkNode $parentNode,
        array $data,
        string $code,
    ): Model {
        return match ($type) {
            NetworkNodeType::Pop => Pop::query()->create([
                'network_node_id' => $node->id,
                'upstream_provider' => $data['upstream_provider'] ?? null,
                'router_model' => $data['router_model'] ?? null,
                'monitoring_enabled' => $data['monitoring_enabled'] ?? true,
            ]),
            NetworkNodeType::Olt => Olt::query()->create([
                'network_node_id' => $node->id,
                'pop_id' => Pop::query()->where('network_node_id', $parentNode?->id)->value('id'),
                'brand' => $data['brand'] ?? null,
                'model' => $data['model'] ?? null,
                'slot_count' => $data['slot_count'] ?? null,
                'pon_port_count' => $data['pon_port_count'] ?? null,
                'ip_address' => $data['ip_address'] ?? null,
                'snmp_community' => $data['snmp_community'] ?? null,
            ]),
            NetworkNodeType::Otb => Otb::query()->create([
                'network_node_id' => $node->id,
                'code' => $code,
                'olt_id' => Olt::query()->where('network_node_id', $parentNode?->id)->value('id'),
                'tray_count' => $data['tray_count'] ?? null,
                'capacity_cores' => $data['capacity_cores'] ?? null,
            ]),
            NetworkNodeType::Odc => Odc::query()->create([
                'network_node_id' => $node->id,
                'code' => $code,
                'otb_id' => Otb::query()->where('network_node_id', $parentNode?->id)->value('id'),
                'port_capacity' => $data['port_capacity'] ?? 0,
                'port_used' => $data['port_used'] ?? 0,
                'split_ratio_default' => $data['split_ratio_default'] ?? null,
                'cores_from_otb' => 0,
                'cores_to_odp' => 0,
            ]),
            NetworkNodeType::Odp => Odp::query()->create([
                'network_node_id' => $node->id,
                'code' => $code,
                'odc_id' => Odc::query()->where('network_node_id', $parentNode?->id)->value('id'),
                'port_capacity' => $data['port_capacity'] ?? 0,
                'port_used' => $data['port_used'] ?? 0,
                'cores_from_odc' => $data['cores_from_odc'] ?? 0,
            ]),
            NetworkNodeType::Customer => Customer::query()->create([
                'network_node_id' => $node->id,
                'code' => $code,
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'service_type' => $data['service_type'] ?? 'home',
                'vlan_tag' => $data['vlan_tag'] ?? null,
                'ip_address' => $data['ip_address'] ?? null,
                'onu_serial' => $data['onu_serial'] ?? null,
                'rx_power_dbm' => $data['rx_power_dbm'] ?? null,
                'tx_power_dbm' => $data['tx_power_dbm'] ?? null,
                'status' => $data['customer_status'] ?? 'active',
                'registered_at' => $data['registered_at'] ?? now()->toDateString(),
            ]),
        };
    }

    /** @param array<string, mixed> $data */
    private function updateDomainRecord(
        NetworkNodeType $type,
        Model $asset,
        ?NetworkNode $parentNode,
        array $data,
        string $code,
    ): void {
        match ($type) {
            NetworkNodeType::Pop => $asset->update([
                'upstream_provider' => $data['upstream_provider'] ?? null,
                'router_model' => $data['router_model'] ?? null,
                'monitoring_enabled' => $data['monitoring_enabled'] ?? true,
            ]),
            NetworkNodeType::Olt => $asset->update([
                'pop_id' => Pop::query()->where('network_node_id', $parentNode?->id)->value('id'),
                'brand' => $data['brand'] ?? null,
                'model' => $data['model'] ?? null,
                'slot_count' => $data['slot_count'] ?? null,
                'pon_port_count' => $data['pon_port_count'] ?? null,
                'ip_address' => $data['ip_address'] ?? null,
                'snmp_community' => $data['snmp_community'] ?? null,
            ]),
            NetworkNodeType::Otb => $asset->update([
                'code' => $code,
                'olt_id' => Olt::query()->where('network_node_id', $parentNode?->id)->value('id'),
                'tray_count' => $data['tray_count'] ?? null,
                'capacity_cores' => $data['capacity_cores'] ?? null,
            ]),
            NetworkNodeType::Odc => $asset->update([
                'code' => $code,
                'otb_id' => Otb::query()->where('network_node_id', $parentNode?->id)->value('id'),
                'port_capacity' => $data['port_capacity'] ?? $asset->port_capacity ?? 0,
                'port_used' => $data['port_used'] ?? $asset->port_used ?? 0,
                'split_ratio_default' => $data['split_ratio_default'] ?? null,
            ]),
            NetworkNodeType::Odp => $asset->update([
                'code' => $code,
                'odc_id' => Odc::query()->where('network_node_id', $parentNode?->id)->value('id'),
                'port_capacity' => $data['port_capacity'] ?? $asset->port_capacity ?? 0,
                'port_used' => $data['port_used'] ?? $asset->port_used ?? 0,
            ]),
            NetworkNodeType::Customer => $asset->update([
                'code' => $code,
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'service_type' => $data['service_type'] ?? 'home',
                'vlan_tag' => $data['vlan_tag'] ?? null,
                'ip_address' => $data['ip_address'] ?? null,
                'onu_serial' => $data['onu_serial'] ?? null,
                'rx_power_dbm' => $data['rx_power_dbm'] ?? null,
                'tx_power_dbm' => $data['tx_power_dbm'] ?? null,
                'status' => $data['customer_status'] ?? $asset->status,
                'registered_at' => $data['registered_at'] ?? $asset->registered_at,
            ]),
        };
    }
}
