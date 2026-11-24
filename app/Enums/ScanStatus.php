<?php

namespace App\Enums;

enum ScanStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
    case NotFood = 'not_food';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Sedang dianalisis',
            self::Completed => 'Selesai',
            self::Failed => 'Gagal',
            self::NotFood => 'Bukan makanan',
        };
    }
}
