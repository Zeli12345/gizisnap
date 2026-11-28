<?php

namespace App\Http\Requests;

use App\Enums\ActivityLevel;
use App\Enums\Gender;
use App\Enums\Goal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class OnboardingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'gender' => ['required', Rule::enum(Gender::class)],
            'birth_date' => ['required', 'date', 'before_or_equal:'.now()->subYears(13)->toDateString(), 'after_or_equal:'.now()->subYears(100)->toDateString()],
            'height_cm' => ['required', 'numeric', 'between:100,250'],
            'weight_kg' => ['required', 'numeric', 'between:20,300'],
            'activity_level' => ['required', Rule::enum(ActivityLevel::class)],
            'goal' => ['required', Rule::enum(Goal::class)],
            'allergies' => ['nullable', 'string', 'max:255'],
            'dietary_preference' => ['nullable', 'string', 'max:100'],
            'health_conditions' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $age = Carbon::parse($this->input('birth_date'))->age;

                if ($age < (int) config('gizisnap.targets.adult_age') && $this->input('goal') === Goal::Lose->value) {
                    $validator->errors()->add('goal', 'Untuk usia di bawah 18 tahun, pilih menjaga atau menaikkan berat.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'birth_date.before_or_equal' => 'Usia minimal 13 tahun.',
            'birth_date.after_or_equal' => 'Periksa lagi tanggal lahir Anda.',
        ];
    }

    /**
     * Alergi diketik dipisah koma, disimpan sebagai daftar.
     *
     * @return list<string>|null
     */
    public function allergyList(): ?array
    {
        $list = collect(explode(',', (string) $this->input('allergies')))
            ->map(fn ($item) => trim($item))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $list ?: null;
    }
}
