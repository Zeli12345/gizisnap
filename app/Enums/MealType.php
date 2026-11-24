<?php

namespace App\Enums;

enum MealType: string
{
    case Breakfast = 'breakfast';
    case Lunch = 'lunch';
    case Dinner = 'dinner';
    case Snack = 'snack';

    public function label(): string
    {
        return match ($this) {
            self::Breakfast => 'Sarapan',
            self::Lunch => 'Makan siang',
            self::Dinner => 'Makan malam',
            self::Snack => 'Camilan',
        };
    }

    /**
     * Tebak waktu makan dari jam, dipakai sebagai pilihan awal formulir
     * dan saat pengguna tidak memilih jenis makan.
     */
    public static function guessFromHour(int $hour): self
    {
        return match (true) {
            $hour >= 4 && $hour < 10 => self::Breakfast,
            $hour >= 10 && $hour < 16 => self::Lunch,
            $hour >= 16 && $hour < 21 => self::Dinner,
            default => self::Snack,
        };
    }
}
