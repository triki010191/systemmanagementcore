<?php

namespace App\Http\Requests\Network;

use App\Enums\OdcSplitterRatio;
use App\Enums\OdcSplitterRouteMode;
use App\Enums\OdcSplitterTargetType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOdcDistributionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('network.edit') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $splitters = $this->input('splitters');

        if (! is_array($splitters)) {
            return;
        }

        foreach ($splitters as $sIndex => $splitter) {
            if (! is_array($splitter)) {
                continue;
            }

            foreach (['input_cable_id', 'input_core_number'] as $field) {
                if (array_key_exists($field, $splitter) && $splitter[$field] === '') {
                    $splitter[$field] = null;
                }
            }

            if (isset($splitter['outputs']) && is_array($splitter['outputs'])) {
                foreach ($splitter['outputs'] as $oIndex => $output) {
                    if (! is_array($output)) {
                        continue;
                    }

                    if (array_key_exists('output_port', $output) && $output['output_port'] !== null && $output['output_port'] !== '') {
                        $output['output_port'] = (int) $output['output_port'];
                    }

                    foreach (['target_node_id', 'outbound_cable_id', 'outbound_core_number', 'downstream_cable_id', 'downstream_core_number'] as $field) {
                        if (array_key_exists($field, $output) && $output[$field] === '') {
                            $output[$field] = null;
                        }
                    }

                    $splitter['outputs'][$oIndex] = $output;
                }
            }

            $splitters[$sIndex] = $splitter;
        }

        $this->merge(['splitters' => $splitters]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'splitters' => ['nullable', 'array'],
            'splitters.*.id' => ['nullable', 'integer', 'exists:odc_splitters,id'],
            'splitters.*.label' => ['nullable', 'string', 'max:100'],
            'splitters.*.input_cable_id' => ['required', 'integer', 'exists:fiber_cables,id'],
            'splitters.*.input_core_number' => ['required', 'integer', 'min:1', 'max:999'],
            'splitters.*.split_ratio' => ['required', Rule::enum(OdcSplitterRatio::class)],
            'splitters.*.outputs' => ['nullable', 'array'],
            'splitters.*.outputs.*.id' => ['nullable', 'integer', 'exists:odc_splitter_outputs,id'],
            'splitters.*.outputs.*.output_port' => ['required', 'integer', 'min:1', 'max:8'],
            'splitters.*.outputs.*.target_type' => ['nullable', Rule::enum(OdcSplitterTargetType::class)],
            'splitters.*.outputs.*.target_node_id' => ['nullable', 'integer', 'exists:network_nodes,id'],
            'splitters.*.outputs.*.route_mode' => ['nullable', Rule::enum(OdcSplitterRouteMode::class)],
            'splitters.*.outputs.*.outbound_cable_id' => ['nullable', 'integer', 'exists:fiber_cables,id'],
            'splitters.*.outputs.*.outbound_core_number' => ['nullable', 'integer', 'min:1', 'max:999'],
            'splitters.*.outputs.*.downstream_cable_id' => ['nullable', 'integer', 'exists:fiber_cables,id'],
            'splitters.*.outputs.*.downstream_core_number' => ['nullable', 'integer', 'min:1', 'max:999'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'splitters.*.input_cable_id' => __('hfnms.odc_dist_input_cable'),
            'splitters.*.input_core_number' => __('hfnms.odc_dist_input_core'),
            'splitters.*.split_ratio' => __('hfnms.odc_dist_ratio'),
            'splitters.*.outputs.*.output_port' => __('hfnms.port'),
            'splitters.*.outputs.*.target_type' => __('hfnms.odc_dist_target_type'),
            'splitters.*.outputs.*.target_node_id' => __('hfnms.odc_dist_target'),
            'splitters.*.outputs.*.outbound_cable_id' => __('hfnms.odc_dist_outbound_cable'),
            'splitters.*.outputs.*.outbound_core_number' => __('hfnms.odc_dist_outbound_core'),
        ];
    }
}
