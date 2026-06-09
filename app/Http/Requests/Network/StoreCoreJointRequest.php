<?php

namespace App\Http\Requests\Network;

use App\Support\GpsCoordinateParser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCoreJointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('cable.manage') ?? false;
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
            if ($this->filled('gps_coordinates') && ! is_numeric($this->input('latitude'))) {
                $validator->errors()->add('gps_coordinates', __('hfnms.gps_invalid'));
            }

            $hasExplicitCores = $this->filled('fiber_core_a_id') && $this->filled('fiber_core_b_id');

            if (! $hasExplicitCores && ! $this->filled('core_number')) {
                $validator->errors()->add('fiber_core_a_id', __('hfnms.joint_core_pair_required'));
            }
        });
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:50', 'unique:core_joints,code'],
            'joint_type' => ['required', Rule::in(['otb_odc', 'odc_odp'])],
            'source_node_id' => ['required', 'exists:network_nodes,id'],
            'target_node_id' => ['required', 'exists:network_nodes,id', 'different:source_node_id'],
            'core_number' => ['nullable', 'integer', 'min:1', 'max:999'],
            'fiber_core_a_id' => ['nullable', 'integer', 'exists:fiber_cores,id'],
            'fiber_core_b_id' => ['nullable', 'integer', 'exists:fiber_cores,id'],
            'gps_coordinates' => ['nullable', 'string', 'max:120'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'splice_loss_db' => ['nullable', 'numeric', 'between:0,5'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'spliced_at' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['active', 'removed'])],
        ];
    }
}
