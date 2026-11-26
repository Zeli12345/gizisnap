<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:191', 'unique:users,email'],
            // Minimal 8 karakter. Tidak memaksa simbol: pengguna sekolah lebih
            // sering lupa kalau aturannya terlalu rumit.
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Isi namamu dulu.',
            'name.min' => 'Nama paling pendek 2 huruf.',
            'email.required' => 'Isi email dulu.',
            'email.email' => 'Alamat email belum benar, contoh: nama@email.com.',
            'email.unique' => 'Email ini sudah terdaftar. Coba masuk saja.',
            'password.required' => 'Buat kata sandi dulu.',
            'password.min' => 'Kata sandi paling pendek 8 huruf.',
            'password.confirmed' => 'Kata sandi dan ulangannya belum sama.',
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nama', 'email' => 'email', 'password' => 'kata sandi'];
    }
}
