<?php

namespace App\Http\Requests\Network;

use App\Enums\NetworkNodeType;
use App\Services\Network\NetworkAssetService;
use App\Support\GpsCoordinateParser;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateNetworkAssetRequest extends StoreNetworkAssetRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('network.edit') ?? false;
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $type = NetworkNodeType::fromRouteSlug((string) $this->route('type'));
        $assetId = (int) $this->route('asset');

        if (! $type || ! $assetId) {
            return;
        }

        $node = app(NetworkAssetService::class)->find($type, $assetId)->networkNode;

        if (! $this->filled('gps_coordinates') && is_numeric($node->latitude) && is_numeric($node->longitude)) {
            $this->merge([
                'latitude' => (float) $node->latitude,
                'longitude' => (float) $node->longitude,
            ]);

            return;
        }

        if ($this->filled('gps_coordinates') && ! is_numeric($this->input('latitude'))) {
            $parsed = GpsCoordinateParser::parse($this->input('gps_coordinates'));

            if ($parsed) {
                $this->merge([
                    'latitude' => $parsed['lat'],
                    'longitude' => $parsed['lng'],
                ]);
            } elseif (is_numeric($node->latitude) && is_numeric($node->longitude)) {
                $this->merge([
                    'latitude' => (float) $node->latitude,
                    'longitude' => (float) $node->longitude,
                ]);
            }
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
        $rules = parent::rules();
        $type = NetworkNodeType::fromRouteSlug((string) $this->route('type'));
        $assetId = (int) $this->route('asset');

        if ($type && $assetId) {
            $node = app(NetworkAssetService::class)->find($type, $assetId)->networkNode;

            $rules['code'] = ['nullable', 'string', 'max:50', Rule::unique('network_nodes', 'code')->ignore($node->id)];

            if ($type->requiresGps() && is_numeric($node->latitude) && is_numeric($node->longitude)) {
                $rules['gps_coordinates'] = ['nullable', 'string', 'max:120'];
                $rules['latitude'] = ['nullable', 'numeric', 'between:-90,90'];
                $rules['longitude'] = ['nullable', 'numeric', 'between:-180,180'];
            }

            if ($type === NetworkNodeType::Odp) {
                unset($rules['cores_from_odc']);
            }
        }

        return $rules;
    }
}
