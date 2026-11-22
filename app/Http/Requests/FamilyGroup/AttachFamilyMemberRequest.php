<?php

namespace App\Http\Requests\FamilyGroup;

use App\Support\CurrentChurch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachFamilyMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('family-groups.manage')
            ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $churchId = app(CurrentChurch::class)->id();

        return [
            'member_profile_id' => [
                'required',
                'integer',
                Rule::exists('member_profiles', 'id')
                    ->where('church_id', $churchId),
            ],
        ];
    }
}
