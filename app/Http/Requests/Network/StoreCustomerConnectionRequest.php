<?php

namespace App\Http\Requests\Network;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('customer.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'customer_id' => [
                'required',
                'exists:customers,id',
                Rule::unique('customer_connections', 'customer_id'),
            ],
            'odp_id' => ['required', 'exists:odps,id'],
            'odp_port_number' => ['required', 'integer', 'min:1', 'max:255'],
            'splitter_port_id' => ['nullable', 'exists:splitter_ports,id'],
            'cable_core_id' => ['nullable', 'exists:fiber_cores,id'],
            'drop_length_m' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'connected_at' => ['nullable', 'date'],
        ];
    }
}
