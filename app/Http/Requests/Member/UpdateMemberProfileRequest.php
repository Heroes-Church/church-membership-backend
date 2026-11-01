<?php

namespace App\Http\Requests\Member;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberProfileRequest extends FormRequest
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
            'first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'middle_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'last_name' => ['sometimes', 'required', 'string', 'max:100'],
            'date_of_birth' => ['sometimes', 'required', 'date', 'before:today'],
            'phone_number' => ['sometimes', 'required', 'string', 'max:30'],
            'gender' => [
                'sometimes',
                'required',
                Rule::in(['Male', 'Female', 'Prefer not to say']),
            ],
            'province' => ['sometimes', 'nullable', 'string', 'max:100'],
            'city_municipality' => ['sometimes', 'nullable', 'string', 'max:100'],
            'barangay' => ['sometimes', 'nullable', 'string', 'max:100'],
            'street_address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'zip_code' => ['sometimes', 'nullable', 'string', 'max:20'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $profileFields = [
                'first_name',
                'middle_name',
                'last_name',
                'date_of_birth',
                'phone_number',
                'gender',
                'province',
                'city_municipality',
                'barangay',
                'street_address',
                'zip_code',
            ];

            if (array_intersect($profileFields, array_keys($this->all())) === []) {
                $validator->errors()->add(
                    'profile',
                    'Provide at least one member profile field to update.'
                );
            }
        });
    }
}
