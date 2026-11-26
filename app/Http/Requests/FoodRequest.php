<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FoodRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'category' => ['required', 'string', 'max:50'],
            'serving_desc' => ['required', 'string', 'max:80'],
            'serving_weight_g' => ['required', 'numeric', 'between:1,5000'],
            'calories_kcal' => ['required', 'numeric', 'between:0,5000'],
            'protein_g' => ['required', 'numeric', 'between:0,999'],
            'carbs_g' => ['required', 'numeric', 'between:0,999'],
            'fat_g' => ['required', 'numeric', 'between:0,999'],
            'fiber_g' => ['nullable', 'numeric', 'between:0,999'],
            'sugar_g' => ['nullable', 'numeric', 'between:0,999'],
            'sodium_mg' => ['nullable', 'numeric', 'between:0,99999'],
            'source' => ['required', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }
}
