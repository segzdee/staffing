<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:20'], // Relaxed regex for now, can be strict later
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers(),
            ],
            'user_type' => ['required', 'in:worker,business,agency'], // Changed 'role' to 'user_type' to match existing form
            'agree_terms' => ['required', 'accepted'], // Changed 'terms' to 'agree_terms'
        ];
    }

    public function messages(): array
    {
        return [
            'agree_terms.accepted' => 'You must accept the terms and conditions.',
        ];
    }
}
