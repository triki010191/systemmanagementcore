<?php

namespace App\Http\Requests\Network;

use App\Enums\NetworkNodeType;
use App\Support\GpsCoordinateParser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreNetworkAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('network.create') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('gps_coordinates')) {
            return;
        }

        $parsed = GpsCoordinateParser::parse($this->input('gps_coordinates'));

        if ($parsed) {
            $this->merge([
                'latitude' => $parsed['lat'],
                'longitude' => $parsed['lng'],
            ]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $type = NetworkNodeType::fromRouteSlug((string) $this->route('type'));

            if (! $type?->requiresGps() || is_numeric($this->input('latitude'))) {
                return;
            }

            if ($this->filled('gps_coordinates')) {
                $validator->errors()->add('gps_coordinates', __('hfnms.gps_invalid'));
            }
        });
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $type = NetworkNodeType::fromRouteSlug($this->route('type'));

        if (! $type) {
            return [];
        }

        $gpsRequired = $type->requiresGps();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'unique:network_nodes,code'],
            'description' => ['nullable', 'string', 'max:1000'],
            'gps_coordinates' => [$gpsRequired ? 'required' : 'nullable', 'string', 'max:120'],
            'latitude' => [$gpsRequired ? 'required' : 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => [$gpsRequired ? 'required' : 'nullable', 'numeric', 'between:-180,180'],
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
            NetworkNodeType::Odp => [
                'port_capacity' => ['nullable', 'integer', 'min:0'],
                'port_used' => ['nullable', 'integer', 'min:0'],
                'cores_from_odc' => ['nullable', 'integer', 'min:0', 'max:999'],
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
