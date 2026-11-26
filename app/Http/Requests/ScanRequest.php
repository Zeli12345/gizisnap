<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScanRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('gizisnap.scan.max_upload_kb')],
        ];
    }

    public function messages(): array
    {
        return [
            'photo.required' => 'Pilih atau ambil foto makanan dulu.',
            'photo.image' => 'Berkas harus berupa foto.',
            'photo.mimes' => 'Format foto harus JPG, PNG, atau WebP.',
            'photo.max' => 'Ukuran foto paling besar 5 MB.',
            'photo.uploaded' => 'Foto gagal diunggah. Ukuran paling besar 5 MB.',
        ];
    }
}
