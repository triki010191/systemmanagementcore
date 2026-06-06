<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('cms.settings.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'settings' => ['nullable', 'array'],
            'settings.*' => ['nullable', 'string', 'max:5000'],
            'modules' => ['nullable', 'array'],
            'modules.*' => ['string', 'max:100'],
        ];
    }
}
