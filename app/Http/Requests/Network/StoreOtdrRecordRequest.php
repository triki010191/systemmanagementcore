<?php

namespace App\Http\Requests\Network;

use Illuminate\Foundation\Http\FormRequest;

class StoreOtdrRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('cable.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'cable_core_id' => ['required', 'exists:fiber_cores,id'],
            'total_loss_db' => ['nullable', 'numeric', 'min:0', 'max:99.99'],
            'distance_km' => ['nullable', 'numeric', 'min:0', 'max:999.999'],
            'fault_distance_km' => ['nullable', 'numeric', 'min:0', 'max:999.999'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'measured_by' => ['nullable', 'exists:users,id'],
            'measured_at' => ['nullable', 'date'],
            'trace_file' => ['nullable', 'file', 'extensions:sor', 'max:20480'],
        ];
    }
}
