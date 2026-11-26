<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatMessageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'Tulis pertanyaan dulu.',
            'message.max' => 'Pesan paling panjang 1000 karakter.',
        ];
    }
}
