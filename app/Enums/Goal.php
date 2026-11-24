<?php

namespace App\Enums;

enum Goal: string
{
    case Lose = 'lose';
    case Maintain = 'maintain';
    case Gain = 'gain';

    public function label(): string
    {
        return match ($this) {
            self::Lose => 'Menurunkan berat',
            self::Maintain => 'Menjaga berat',
            self::Gain => 'Menaikkan berat',
        };
    }

    public function adjustment(): float
    {
        return (float) config('gizisnap.targets.goal_adjustments.'.$this->value);
    }
}
