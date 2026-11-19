<?php

namespace App\Http\Requests\Auth;

use App\Support\CurrentChurch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->where('church_id', app(CurrentChurch::class)->id())
                    ->whereNotNull('email_verified_at'),
            ],
            'password' => ['required', 'string', Password::defaults()],
            'terms_accepted' => ['required', 'accepted'],
        ];
    }
}
