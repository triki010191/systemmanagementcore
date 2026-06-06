<?php

namespace App\Http\Requests\Network;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('network.edit') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'photo' => ['required', 'image', 'max:5120'],
            'photo_type' => ['required', Rule::in(['location', 'device', 'label', 'splice', 'other'])],
            'caption' => ['nullable', 'string', 'max:255'],
        ];
    }
}
