<?php

namespace App\Http\Requests;

use App\Enums\MealType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Dipakai untuk catatan makan manual, ubah catatan, dan simpan hasil scan.
 */
class MealRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            // Waktu makan tidak wajib dipilih pengguna: kalau kosong, server
            // menebaknya dari jam makan. Ini yang membuat pencatatan terasa
            // seperti jurnal, bukan formulir empat kotak.
            'meal_type' => ['nullable', Rule::enum(MealType::class)],
            'eaten_date' => ['required', 'date', 'before_or_equal:today'],
            'eaten_time' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.food_id' => ['nullable', 'integer', 'exists:foods,id'],
            'items.*.name' => ['required', 'string', 'max:120'],
            'items.*.portion_desc' => ['nullable', 'string', 'max:80'],
            'items.*.weight_g' => ['nullable', 'numeric', 'between:0,5000'],
            'items.*.calories_kcal' => ['required', 'numeric', 'between:0,5000'],
            'items.*.protein_g' => ['required', 'numeric', 'between:0,999'],
            'items.*.carbs_g' => ['required', 'numeric', 'between:0,999'],
            'items.*.fat_g' => ['required', 'numeric', 'between:0,999'],
            'items.*.fiber_g' => ['nullable', 'numeric', 'between:0,999'],
            'items.*.sugar_g' => ['nullable', 'numeric', 'between:0,999'],
            'items.*.sodium_mg' => ['nullable', 'numeric', 'between:0,99999'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Tambahkan minimal satu makanan.',
            'items.min' => 'Tambahkan minimal satu makanan.',
            'eaten_date.before_or_equal' => 'Tanggal makan tidak boleh di masa depan.',
        ];
    }

    public function attributes(): array
    {
        return [
            'items.*.name' => 'nama makanan',
            'items.*.calories_kcal' => 'kalori',
            'items.*.protein_g' => 'protein',
            'items.*.carbs_g' => 'karbohidrat',
            'items.*.fat_g' => 'lemak',
            'items.*.portion_desc' => 'porsi',
            'items.*.weight_g' => 'berat',
        ];
    }

    public function eatenAt(): Carbon
    {
        return Carbon::parse($this->input('eaten_date').' '.$this->input('eaten_time'));
    }

    /**
     * Waktu makan: pakai pilihan pengguna kalau ada, kalau tidak tebak dari jam.
     */
    public function mealType(): MealType
    {
        $value = $this->input('meal_type');

        if (filled($value)) {
            return MealType::from($value);
        }

        return MealType::guessFromHour((int) $this->eatenAt()->format('G'));
    }

    public function mealItems(): array
    {
        return array_values($this->validated('items'));
    }
}
