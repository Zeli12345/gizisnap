<?php

namespace App\Http\Controllers;

use App\Models\Food;
use Illuminate\Http\Request;

class FoodSearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $term = trim((string) $request->query('q'));

        $foods = Food::active()
            ->when($term !== '', fn ($q) => $q->where('name', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%'))
            ->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END', [$term.'%'])
            ->orderBy('name')
            ->limit(12)
            ->get(['id', 'name', 'category', 'serving_desc', 'serving_weight_g', 'calories_kcal', 'protein_g', 'carbs_g', 'fat_g', 'fiber_g', 'sugar_g', 'sodium_mg']);

        return response()->json(['data' => $foods]);
    }
}
