<?php

namespace App\Services;

use App\Models\DailyTarget;
use App\Models\Meal;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class DailySummaryService
{
    /**
     * Ringkasan satu hari: target, total, sisa, dan daftar makan per waktu makan.
     */
    public function forDate(User $user, CarbonInterface $date): array
    {
        $date = CarbonImmutable::parse($date)->startOfDay();
        $target = $user->targetFor($date);

        $meals = $user->meals()
            ->with('items')
            ->whereBetween('eaten_at', [$date, $date->endOfDay()])
            ->orderBy('eaten_at')
            ->get();

        $totals = [
            'calories_kcal' => round($meals->sum('total_calories_kcal'), 1),
            'protein_g' => round($meals->sum('total_protein_g'), 1),
            'carbs_g' => round($meals->sum('total_carbs_g'), 1),
            'fat_g' => round($meals->sum('total_fat_g'), 1),
            'fiber_g' => round($meals->sum('total_fiber_g'), 1),
            'sugar_g' => round($meals->sum('total_sugar_g'), 1),
            'sodium_mg' => round($meals->sum('total_sodium_mg'), 1),
        ];

        $water = (int) $user->waterLogs()
            ->whereBetween('logged_at', [$date, $date->endOfDay()])
            ->sum('amount_ml');

        return [
            'date' => $date,
            'target' => $target,
            'totals' => $totals,
            'remaining_kcal' => $target ? (int) round($target->calories_kcal - $totals['calories_kcal']) : null,
            'meals' => $meals,
            'water_ml' => $water,
            'water_target_ml' => $target?->water_ml ?? (int) config('gizisnap.targets.water_ml'),
        ];
    }

    /**
     * Data harian untuk grafik laporan, dari hari tertua ke terbaru.
     *
     * @return Collection<int, array{date: CarbonImmutable, calories_kcal: float, protein_g: float, carbs_g: float, fat_g: float, target_kcal: ?int, logged: bool}>
     */
    public function series(User $user, int $days, ?CarbonInterface $until = null): Collection
    {
        $end = CarbonImmutable::parse($until ?? now())->endOfDay();
        $start = $end->subDays($days - 1)->startOfDay();

        $meals = $user->meals()
            ->whereBetween('eaten_at', [$start, $end])
            ->get(['eaten_at', 'total_calories_kcal', 'total_protein_g', 'total_carbs_g', 'total_fat_g'])
            ->groupBy(fn (Meal $meal) => $meal->eaten_at->toDateString());

        $targets = $user->dailyTargets()->orderBy('effective_from')->get();

        return collect(range(0, $days - 1))->map(function (int $offset) use ($start, $meals, $targets) {
            $day = $start->addDays($offset);
            $dayMeals = $meals->get($day->toDateString(), collect());
            $target = $targets->filter(fn (DailyTarget $t) => $t->effective_from->lte($day))->last() ?? $targets->first();

            return [
                'date' => $day,
                'calories_kcal' => round($dayMeals->sum('total_calories_kcal'), 1),
                'protein_g' => round($dayMeals->sum('total_protein_g'), 1),
                'carbs_g' => round($dayMeals->sum('total_carbs_g'), 1),
                'fat_g' => round($dayMeals->sum('total_fat_g'), 1),
                'target_kcal' => $target?->calories_kcal,
                'logged' => $dayMeals->isNotEmpty(),
            ];
        });
    }

    /**
     * Ringkasan periode untuk halaman laporan.
     */
    public function periodStats(Collection $series): array
    {
        $logged = $series->where('logged', true);
        $count = max($logged->count(), 1);

        $withinTarget = $logged->filter(function (array $day) {
            if (! $day['target_kcal']) {
                return false;
            }

            $ratio = $day['calories_kcal'] / $day['target_kcal'];

            return $ratio >= 0.9 && $ratio <= 1.1;
        })->count();

        return [
            'days' => $series->count(),
            'logged_days' => $logged->count(),
            'avg_calories' => (int) round($logged->sum('calories_kcal') / $count),
            'avg_protein' => round($logged->sum('protein_g') / $count, 1),
            'avg_carbs' => round($logged->sum('carbs_g') / $count, 1),
            'avg_fat' => round($logged->sum('fat_g') / $count, 1),
            'within_target_days' => $withinTarget,
        ];
    }

    /**
     * Jumlah hari berturut-turut sampai hari ini (atau kemarin) yang punya catatan makan.
     */
    public function streak(User $user): int
    {
        $dates = $user->meals()
            ->where('eaten_at', '>=', now()->subDays(60)->startOfDay())
            ->pluck('eaten_at')
            ->map(fn ($d) => $d->toDateString())
            ->unique()
            ->flip();

        $day = CarbonImmutable::today();

        if (! $dates->has($day->toDateString())) {
            $day = $day->subDay();
        }

        $streak = 0;

        while ($dates->has($day->toDateString())) {
            $streak++;
            $day = $day->subDay();
        }

        return $streak;
    }

    /**
     * Konteks singkat tentang pengguna untuk chatbot.
     */
    public function contextForChat(User $user): string
    {
        $profile = $user->healthProfile;
        $summary = $this->forDate($user, now());
        $target = $summary['target'];
        $lines = [];

        $lines[] = 'Tanggal dan jam sekarang: '.now()->translatedFormat('l, j F Y H:i');
        $lines[] = 'Nama panggilan: '.$user->firstName();

        if ($profile) {
            $lines[] = sprintf(
                'Profil: %s, %d tahun, tinggi %s cm, berat %s kg, aktivitas %s, tujuan %s.',
                $profile->gender->label(), $profile->age(), $profile->height_cm, $profile->weight_kg,
                mb_strtolower($profile->activity_level->label()), mb_strtolower($profile->goal->label()),
            );

            if ($profile->allergies) {
                $lines[] = 'Alergi: '.implode(', ', $profile->allergies).'.';
            }

            if ($profile->dietary_preference) {
                $lines[] = 'Preferensi makan: '.$profile->dietary_preference.'.';
            }
        }

        if ($target) {
            $lines[] = sprintf('Target harian: %d kkal, protein %s g, karbohidrat %s g, lemak %s g, air %d ml.',
                $target->calories_kcal, $target->protein_g, $target->carbs_g, $target->fat_g, $target->water_ml);
        }

        $t = $summary['totals'];
        $lines[] = sprintf('Asupan hari ini: %s kkal, protein %s g, karbohidrat %s g, lemak %s g, air %d ml.',
            $t['calories_kcal'], $t['protein_g'], $t['carbs_g'], $t['fat_g'], $summary['water_ml']);

        if ($summary['remaining_kcal'] !== null) {
            $lines[] = 'Sisa kalori hari ini: '.$summary['remaining_kcal'].' kkal.';
        }

        $mealLines = $summary['meals']->map(fn (Meal $meal) => sprintf('- %s %s: %s (%s kkal)',
            $meal->meal_type->label(), $meal->eaten_at->format('H:i'), $meal->title, $meal->total_calories_kcal));

        $lines[] = $mealLines->isEmpty()
            ? 'Makanan hari ini: belum ada catatan.'
            : "Makanan hari ini:\n".$mealLines->implode("\n");

        return implode("\n", $lines);
    }
}
