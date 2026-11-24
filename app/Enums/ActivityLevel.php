<?php

namespace App\Enums;

enum ActivityLevel: string
{
    case Sedentary = 'sedentary';
    case Light = 'light';
    case Moderate = 'moderate';
    case Active = 'active';
    case VeryActive = 'very_active';

    public function label(): string
    {
        return match ($this) {
            self::Sedentary => 'Jarang bergerak',
            self::Light => 'Sedikit aktif',
            self::Moderate => 'Cukup aktif',
            self::Active => 'Aktif',
            self::VeryActive => 'Sangat aktif',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Sedentary => 'Banyak duduk, hampir tidak olahraga',
            self::Light => 'Olahraga ringan 1 sampai 3 kali seminggu',
            self::Moderate => 'Olahraga 3 sampai 5 kali seminggu',
            self::Active => 'Olahraga berat 6 sampai 7 kali seminggu',
            self::VeryActive => 'Latihan berat setiap hari atau kerja fisik',
        };
    }

    public function factor(): float
    {
        return (float) config('gizisnap.targets.activity_factors.'.$this->value);
    }
}
