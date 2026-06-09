<?php

namespace App\Http\Requests\Network;

use App\Enums\CoreStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('core.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(CoreStatus::class)],
            'source_node_id' => ['nullable', 'exists:network_nodes,id'],
            'target_node_id' => ['nullable', 'exists:network_nodes,id', 'different:source_node_id'],
            'loss_db' => ['nullable', 'numeric', 'min:0', 'max:99.99'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'status' => __('hfnms.status'),
            'source_node_id' => __('hfnms.source_node'),
            'target_node_id' => __('hfnms.target_node'),
            'loss_db' => __('hfnms.total_loss'),
        ];
    }
}
