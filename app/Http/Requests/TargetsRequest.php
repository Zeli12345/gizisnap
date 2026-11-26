<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TargetsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'calories_kcal' => ['required', 'integer', 'between:1000,5000'],
            'protein_g' => ['required', 'numeric', 'between:10,400'],
            'carbs_g' => ['required', 'numeric', 'between:20,800'],
            'fat_g' => ['required', 'numeric', 'between:10,300'],
            'water_ml' => ['required', 'integer', 'between:500,6000'],
        ];
    }
}
