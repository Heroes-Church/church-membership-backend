<?php

namespace App\Http\Requests\Auth;

use App\Support\CurrentChurch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class MemberRegisterRequest extends FormRequest
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
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->where('church_id', app(CurrentChurch::class)->id()),
            ],
            'password' => ['required', 'string', Password::defaults()],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'phone_number' => ['required', 'string', 'max:30'],
            'gender' => ['required', 'in:Male,Female,Prefer not to say'],
            'province' => ['nullable', 'string', 'max:100'],
            'city_municipality' => ['nullable', 'string', 'max:100'],
            'barangay' => ['nullable', 'string', 'max:100'],
            'street_address' => ['nullable', 'string', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'terms_accepted' => ['required', 'accepted'],
        ];
    }
}
