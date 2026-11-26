<?php

namespace App\Http\Requests;

class ProfileRequest extends OnboardingRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'name' => ['required', 'string', 'max:100'],
        ];
    }
}
