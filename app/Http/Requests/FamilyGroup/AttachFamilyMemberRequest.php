<?php

namespace App\Http\Requests\FamilyGroup;

use Illuminate\Foundation\Http\FormRequest;

class AttachFamilyMemberRequest extends FormRequest
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
            'member_profile_id' => [
                'required',
                'integer',
                'exists:member_profiles,id',
            ],
        ];
    }
}
