<?php

namespace App\Http\Controllers;

use App\Enums\ActivityLevel;
use App\Enums\Goal;
use App\Models\Food;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    public function __invoke(Request $request)
    {
        if ($request->user()) {
            return redirect()->route('dashboard');
        }

        // Makanan asli dari daftar, untuk dua pita berjalan di bawah bagian pembuka.
        $foods = Food::active()
            ->where('calories_kcal', '>', 0)
            ->inRandomOrder()
            ->limit(24)
            ->get(['name', 'serving_desc', 'calories_kcal']);

        return view('landing', [
            'foods' => $foods,
            'foodCount' => Food::active()->count(),
            'levels' => ActivityLevel::cases(),
            'goals' => Goal::cases(),
            'calculator' => [
                'factors' => config('gizisnap.targets.activity_factors'),
                'adjustments' => config('gizisnap.targets.goal_adjustments'),
                'adultAge' => (int) config('gizisnap.targets.adult_age'),
                'split' => config('gizisnap.targets.macro_split'),
            ],
            // Piring contoh di langkah kedua. Angkanya sama dengan yang tampil di pratinjau pembuka.
            'demo' => [
                'target' => 2000,
                'items' => [
                    ['name' => 'Nasi putih', 'kcal' => 195],
                    ['name' => 'Ayam bakar', 'kcal' => 226],
                    ['name' => 'Sayur asem', 'kcal' => 54],
                    ['name' => 'Tempe goreng', 'kcal' => 118],
                    ['name' => 'Sambal', 'kcal' => 21],
                ],
            ],
        ]);
    }
}
