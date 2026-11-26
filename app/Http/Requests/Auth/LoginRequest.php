<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Isi email dulu.',
            'email.email' => 'Alamat email belum benar.',
            'password.required' => 'Isi kata sandi dulu.',
        ];
    }

    public function attributes(): array
    {
        return ['email' => 'email', 'password' => 'kata sandi'];
    }
}
