<?php

namespace App\Http\Requests\Network;

use App\Enums\NetworkNodeType;
use App\Enums\SplitterRatio;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNetworkAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('network.create') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $type = NetworkNodeType::fromRouteSlug($this->route('type'));

        if (! $type) {
            return [];
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'unique:network_nodes,code'],
            'description' => ['nullable', 'string', 'max:1000'],
            'latitude' => [$type->requiresGps() ? 'required' : 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => [$type->requiresGps() ? 'required' : 'nullable', 'numeric', 'between:-180,180'],
            'address' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::in(['active', 'inactive', 'maintenance', 'fault'])],
            'parent_id' => [$type->expectedParentType() ? 'required' : 'nullable', 'exists:network_nodes,id'],
        ];

        return array_merge($rules, $this->typeRules($type));
    }

    /** @return array<string, mixed> */
    private function typeRules(NetworkNodeType $type): array
    {
        return match ($type) {
            NetworkNodeType::Pop => [
                'upstream_provider' => ['nullable', 'string', 'max:100'],
                'router_model' => ['nullable', 'string', 'max:100'],
                'monitoring_enabled' => ['nullable', 'boolean'],
            ],
            NetworkNodeType::Olt => [
                'brand' => ['nullable', 'string', 'max:100'],
                'model' => ['nullable', 'string', 'max:100'],
                'slot_count' => ['nullable', 'integer', 'min:1', 'max:32'],
                'pon_port_count' => ['nullable', 'integer', 'min:1', 'max:128'],
                'ip_address' => ['nullable', 'ip'],
                'snmp_community' => ['nullable', 'string', 'max:100'],
            ],
            NetworkNodeType::Otb => [
                'tray_count' => ['nullable', 'integer', 'min:1', 'max:32'],
                'capacity_cores' => ['nullable', 'integer', Rule::in([12, 24, 48, 96])],
            ],
            NetworkNodeType::Odc => [
                'port_capacity' => ['nullable', 'integer', 'min:0'],
                'port_used' => ['nullable', 'integer', 'min:0'],
                'split_ratio_default' => ['nullable', 'string', 'max:10'],
            ],
            NetworkNodeType::Splitter => [
                'ratio' => ['required', Rule::enum(SplitterRatio::class)],
                'brand' => ['nullable', 'string', 'max:100'],
            ],
            NetworkNodeType::Odp => [
                'port_capacity' => ['nullable', 'integer', 'min:0'],
                'port_used' => ['nullable', 'integer', 'min:0'],
            ],
            NetworkNodeType::Customer => [
                'phone' => ['nullable', 'string', 'max:20'],
                'email' => ['nullable', 'email', 'max:255'],
                'service_type' => ['nullable', Rule::in(['home', 'business'])],
                'vlan_tag' => ['nullable', 'integer', 'min:1', 'max:4094'],
                'ip_address' => ['nullable', 'ip'],
                'onu_serial' => ['nullable', 'string', 'max:100'],
                'rx_power_dbm' => ['nullable', 'numeric', 'between:-40,10'],
                'tx_power_dbm' => ['nullable', 'numeric', 'between:-10,10'],
                'customer_status' => ['nullable', Rule::in(['active', 'suspended', 'terminated'])],
                'registered_at' => ['nullable', 'date'],
            ],
        };
    }
}
