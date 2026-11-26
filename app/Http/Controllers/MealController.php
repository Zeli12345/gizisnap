<?php

namespace App\Http\Controllers;

use App\Http\Requests\MealRequest;
use App\Models\Food;
use App\Models\Meal;
use App\Services\DailySummaryService;
use App\Services\MealService;
use App\Services\ScanService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MealController extends Controller
{
    public function __construct(private readonly MealService $meals) {}

    public function index(Request $request, DailySummaryService $summary)
    {
        $user = $request->user();

        $days = $user->meals()
            ->with('items')
            ->where('eaten_at', '>=', now()->subDays(13)->startOfDay())
            ->orderByDesc('eaten_at')
            ->get()
            ->groupBy(fn (Meal $meal) => $meal->eaten_at->toDateString());

        $targets = $summary->series($user, 14)->keyBy(fn ($day) => $day['date']->toDateString());

        return view('meals.index', [
            'days' => $days,
            'targets' => $targets,
        ]);
    }

    public function create(Request $request)
    {
        $date = $request->date('date') ?? now();
        $preset = $request->integer('food') ? Food::query()->active()->find($request->integer('food')) : null;

        return view('meals.form', [
            'meal' => null,
            'defaults' => [
                'title' => $preset?->name ?? '',
                'eaten_date' => CarbonImmutable::parse($date)->min(now())->toDateString(),
                'eaten_time' => now()->format('H:i'),
                'notes' => '',
                'items' => $preset ? [[
                    'food_id' => $preset->id,
                    'name' => $preset->name,
                    'portion_desc' => $preset->serving_desc,
                    'weight_g' => $preset->serving_weight_g,
                    'calories_kcal' => $preset->calories_kcal,
                    'protein_g' => $preset->protein_g,
                    'carbs_g' => $preset->carbs_g,
                    'fat_g' => $preset->fat_g,
                    'fiber_g' => $preset->fiber_g,
                    'sugar_g' => $preset->sugar_g,
                    'sodium_mg' => $preset->sodium_mg,
                ]] : [],
            ],
        ]);
    }

    public function store(MealRequest $request)
    {
        $meal = $this->meals->create($request->user(), [
            'meal_type' => $request->mealType(),
            'eaten_at' => $request->eatenAt(),
            'title' => $request->validated('title'),
            'notes' => $request->validated('notes'),
            'source' => 'manual',
        ], $request->mealItems());

        return redirect()
            ->route('dashboard', ['date' => $meal->eaten_at->toDateString()])
            ->with('success', "{$meal->title} tersimpan.");
    }

    public function edit(Meal $meal)
    {
        Gate::authorize('update', $meal);
        $meal->load('items');

        return view('meals.form', [
            'meal' => $meal,
            'defaults' => [
                'title' => $meal->title,
                'eaten_date' => $meal->eaten_at->toDateString(),
                'eaten_time' => $meal->eaten_at->format('H:i'),
                'notes' => $meal->notes,
                'items' => $meal->items->map->only([
                    'food_id', 'name', 'portion_desc', 'weight_g', 'calories_kcal', 'protein_g', 'carbs_g', 'fat_g', 'fiber_g', 'sugar_g', 'sodium_mg',
                ])->values()->all(),
            ],
        ]);
    }

    public function update(MealRequest $request, Meal $meal)
    {
        Gate::authorize('update', $meal);

        $original = $meal->items()->get()->keyBy('name');
        $items = collect($request->mealItems())->map(function (array $item) use ($original) {
            $before = $original->get($item['name']);
            $item['is_edited'] = ! $before || $before->is_edited
                || (float) $before->calories_kcal !== (float) $item['calories_kcal'];

            return $item;
        })->all();

        $this->meals->update($meal, [
            'meal_type' => $request->mealType(),
            'eaten_at' => $request->eatenAt(),
            'title' => $request->validated('title'),
            'notes' => $request->validated('notes'),
        ], $items);

        return redirect()
            ->route('dashboard', ['date' => $meal->eaten_at->toDateString()])
            ->with('success', 'Perubahan tersimpan.');
    }

    public function destroy(Meal $meal)
    {
        Gate::authorize('delete', $meal);

        $date = $meal->eaten_at->toDateString();
        $title = $meal->title;
        $meal->delete();

        return redirect()
            ->route('dashboard', ['date' => $date])
            ->with('success', "{$title} dihapus.");
    }

    /**
     * Catat ulang makanan yang sama untuk sekarang.
     */
    public function repeat(Meal $meal)
    {
        Gate::authorize('view', $meal);
        $meal->load('items');

        $copy = $this->meals->create(request()->user(), [
            'meal_type' => ScanService::defaultMealType(),
            'eaten_at' => now(),
            'title' => $meal->title,
            'notes' => $meal->notes,
            'source' => 'manual',
        ], $meal->items->map->only([
            'food_id', 'name', 'portion_desc', 'weight_g', 'calories_kcal', 'protein_g', 'carbs_g', 'fat_g', 'fiber_g', 'sugar_g', 'sodium_mg',
        ])->all());

        return redirect()->route('dashboard')->with('success', "{$copy->title} dicatat lagi untuk hari ini.");
    }
}
