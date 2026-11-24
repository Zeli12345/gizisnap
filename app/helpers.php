<?php

if (! function_exists('gizi_num')) {
    /**
     * Format angka gaya Indonesia: 1.250 atau 12,5.
     */
    function gizi_num(int|float|string|null $value, int $decimals = 0): string
    {
        $value = (float) $value;

        // Buang ,0 di belakang supaya "12,0" tampil "12".
        if ($decimals > 0 && round($value, $decimals) == round($value)) {
            $decimals = 0;
        }

        return number_format($value, $decimals, ',', '.');
    }
}
