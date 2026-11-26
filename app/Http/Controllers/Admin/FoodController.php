<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FoodRequest;
use App\Models\Food;
use Illuminate\Http\Request;

class FoodController extends Controller
{
    public function index(Request $request)
    {
        $term = trim((string) $request->query('q'));

        $foods = Food::query()
            ->when($term !== '', fn ($q) => $q->where('name', 'like', "%{$term}%"))
            ->orderBy('category')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.foods.index', ['foods' => $foods, 'term' => $term]);
    }

    public function create()
    {
        return view('admin.foods.form', ['food' => new Food(['is_active' => true, 'source' => 'TKPI Kemenkes']), 'categories' => $this->categories()]);
    }

    public function store(FoodRequest $request)
    {
        $food = Food::create($request->validated());

        return redirect()->route('admin.foods.index')->with('success', "{$food->name} ditambahkan.");
    }

    public function edit(Food $food)
    {
        return view('admin.foods.form', ['food' => $food, 'categories' => $this->categories()]);
    }

    public function update(FoodRequest $request, Food $food)
    {
        $food->update($request->validated());

        return redirect()->route('admin.foods.index')->with('success', "{$food->name} diperbarui.");
    }

    public function destroy(Food $food)
    {
        $name = $food->name;
        $food->delete();

        return redirect()->route('admin.foods.index')->with('success', "{$name} dihapus.");
    }

    private function categories(): array
    {
        return Food::query()->distinct()->orderBy('category')->pluck('category')->all()
            ?: ['Makanan pokok', 'Lauk hewani', 'Lauk nabati', 'Sayur', 'Buah', 'Minuman', 'Jajanan'];
    }
}
