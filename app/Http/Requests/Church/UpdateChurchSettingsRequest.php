<?php

namespace App\Http\Requests\Church;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChurchSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'contact_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'contact_phone' => ['sometimes', 'nullable', 'string', 'max:32'],
            'address' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'primary_color' => [
                'sometimes',
                'nullable',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],
            'secondary_color' => [
                'sometimes',
                'nullable',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],
            'logo' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['sometimes', 'boolean'],
        ];
    }
}
