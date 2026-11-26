<?php

namespace App\Services;

use App\Models\Meal;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Menyusun makanan satu hari menjadi urutan waktu, bukan empat kotak tetap.
 *
 * Aplikasi lain memaksa pengguna memilih "sarapan / makan siang / makan malam /
 * camilan". Padahal orang makan kapan saja: sarapan jam 09.30, ngemil jam 11,
 * makan siang jam 14, dan seterusnya. GiziSnap menampilkannya sebagai satu
 * alur hari supaya terasa seperti catatan, bukan formulir.
 */
class MealTimeline
{
    /**
     * Celah waktu (menit) yang dianggap bagian dari satu sesi makan.
     * Dipakai untuk menggabungkan beberapa catatan yang dimakan bersamaan.
     */
    private const SESSION_GAP_MINUTES = 45;

    /**
     * @return list<array{
     *     at: CarbonInterface,
     *     label: string,
     *     part_of_day: string,
     *     meals: list<Meal>,
     *     calories: float,
     *     protein: float,
     *     carbs: float,
     *     fat: float
     * }>
     */
    public function forDate(User $user, CarbonInterface $date): array
    {
        $meals = $user->meals()
            ->with('items')
            ->whereBetween('eaten_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])
            ->orderBy('eaten_at')
            ->get();

        if ($meals->isEmpty()) {
            return [];
        }

        return $this->group($meals)
            ->values()
            ->map(fn (Collection $session) => $this->summarize($session))
            ->all();
    }

    /**
     * Gabungkan catatan yang waktunya berdekatan menjadi satu sesi.
     *
     * @param  Collection<int, Meal>  $meals
     * @return Collection<int, Collection<int, Meal>>
     */
    private function group(Collection $meals): Collection
    {
        $sessions = collect();
        $current = collect();
        $lastAt = null;

        foreach ($meals as $meal) {
            if ($lastAt !== null && $lastAt->diffInMinutes($meal->eaten_at) > self::SESSION_GAP_MINUTES) {
                $sessions->push($current);
                $current = collect();
            }

            $current->push($meal);
            $lastAt = $meal->eaten_at;
        }

        if ($current->isNotEmpty()) {
            $sessions->push($current);
        }

        return $sessions;
    }

    /**
     * @param  Collection<int, Meal>  $session
     * @return array{
     *     at: CarbonInterface,
     *     label: string,
     *     part_of_day: string,
     *     meals: list<Meal>,
     *     calories: float,
     *     protein: float,
     *     carbs: float,
     *     fat: float
     * }
     */
    private function summarize(Collection $session): array
    {
        $first = $session->first();
        $hour = (int) $first->eaten_at->format('G');

        $titles = $session->pluck('title')->unique();

        return [
            'at' => $first->eaten_at,
            'label' => $titles->count() === 1
                ? $titles->first()
                : $titles->take(2)->implode(' + ').($titles->count() > 2 ? ' +'.($titles->count() - 2) : ''),
            'part_of_day' => $this->partOfDay($hour),
            'meals' => $session->all(),
            'calories' => round($session->sum('total_calories_kcal'), 1),
            'protein' => round($session->sum('total_protein_g'), 1),
            'carbs' => round($session->sum('total_carbs_g'), 1),
            'fat' => round($session->sum('total_fat_g'), 1),
        ];
    }

    /**
     * Sebutan waktu yang akrab, bukan nama waktu makan yang kaku.
     */
    private function partOfDay(int $hour): string
    {
        return match (true) {
            $hour < 4 => 'Tengah malam',
            $hour < 10 => 'Pagi',
            $hour < 14 => 'Siang',
            $hour < 17 => 'Sore',
            $hour < 21 => 'Petang',
            default => 'Malam',
        };
    }
}
