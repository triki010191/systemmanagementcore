<?php

namespace App\Http\Requests\Network;

use App\Http\Requests\Concerns\ResolvesRouteModelId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCableRequest extends FormRequest
{
    use ResolvesRouteModelId;

    public function authorize(): bool
    {
        return $this->user()?->can('cable.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $cableId = $this->routeModelId('cable');

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('fiber_cables', 'code')->ignore($cableId)],
            'name' => ['required', 'string', 'max:255'],
            'manufacturer' => ['nullable', 'string', 'max:100'],
            'fiber_type' => ['nullable', 'string', 'max:50'],
            'jacket_rating' => ['nullable', 'string', 'max:50'],
            'cable_type' => ['required', Rule::in(['backbone', 'distribution', 'drop'])],
            'core_count' => ['required', 'integer', Rule::in([12, 24, 48, 96])],
            'tube_count' => ['nullable', 'integer', 'min:1', 'max:12'],
            'length_meters' => ['nullable', 'numeric', 'min:0'],
            'installed_at' => ['nullable', 'date'],
            'start_node_id' => ['nullable', 'exists:network_nodes,id'],
            'end_node_id' => ['nullable', 'exists:network_nodes,id', Rule::when(
                fn () => $this->filled('end_node_id') && $this->filled('start_node_id'),
                'different:start_node_id',
            )],
            'status' => ['required', Rule::in(['active', 'inactive', 'damaged', 'planned'])],
            'route_geometry_text' => ['nullable', 'string'],
            'snap_to_roads' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->filled('core_count') && ! $this->filled('tube_count')) {
            $merge['tube_count'] = max(1, (int) ceil(((int) $this->input('core_count')) / 12));
        }

        foreach (['start_node_id', 'end_node_id'] as $field) {
            if ($this->has($field) && $this->input($field) === '') {
                $merge[$field] = null;
            }
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'code' => __('hfnms.cable_code'),
            'name' => __('hfnms.cable_name'),
            'cable_type' => __('hfnms.cable_type'),
            'core_count' => __('hfnms.core_count'),
            'start_node_id' => __('hfnms.start_node'),
            'end_node_id' => __('hfnms.end_node'),
            'status' => __('hfnms.status'),
        ];
    }
}
