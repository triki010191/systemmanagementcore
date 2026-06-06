<?php

namespace App\Http\Requests\Network;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaintenanceScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('maintenance.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'network_node_id' => ['nullable', 'exists:network_nodes,id'],
            'schedule_type' => ['required', Rule::in(['preventive', 'corrective', 'inspection'])],
            'status' => ['required', Rule::in(['scheduled', 'in_progress', 'completed', 'cancelled'])],
            'priority' => ['required', Rule::in(['low', 'medium', 'high'])],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'scheduled_at' => ['required', 'date'],
        ];
    }
}
