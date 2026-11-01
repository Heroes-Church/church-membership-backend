<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRoleRequest extends FormRequest
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
            'role' => [
                'required',
                'string',
                Rule::in(['Admin', 'Leader', 'Member', 'Guest']),
            ],
            'membership_class_attended' => [
                'exclude_unless:role,Member',
                'required',
                'accepted',
            ],
        ];
    }
}
