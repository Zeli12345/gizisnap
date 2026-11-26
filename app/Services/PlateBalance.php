<?php

namespace App\Services;

use App\Enums\FoodGroup;
use App\Models\Meal;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Menilai susunan piring, bukan hanya jumlah kalori.
 *
 * Inilah pembeda GiziSnap dari aplikasi penghitung kalori biasa: yang
 * ditampilkan bukan cuma "sudah 745 kkal", tapi "piringmu hari ini sudah
 * ada pokok dan protein, tapi belum ada sayur dan buah".
 *
 * Acuan pembagian: "Isi Piringku" Kemenkes RI.
 */
class PlateBalance
{
    /**
     * Bagian tiap kelompok di piring. Dihitung dari berat, bukan kalori,
     * karena acuannya porsi makan, bukan energi.
     *
     * @var array<string, float>
     */
    private const SHARE = [
        'staple' => 0.35,
        'protein' => 0.20,
        'vegetable' => 0.30,
        'fruit' => 0.15,
    ];

    /**
     * Ringkasan piring satu hari.
     *
     * @return array{
     *     groups: list<array{group: FoodGroup, grams: float, share: float, target: float, covered: bool, items: int}>,
     *     covered: int,
     *     total: int,
     *     is_balanced: bool,
     *     missing: list<FoodGroup>,
     *     headline: string
     * }
     */
    public function forDate(User $user, CarbonInterface $date): array
    {
        $meals = $user->meals()
            ->with('items')
            ->whereBetween('eaten_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])
            ->get();

        $grams = $this->gramsByGroup($meals);

        $total = array_sum($grams);
        $targets = self::SHARE;
        $plated = FoodGroup::plated();

        $groups = [];
        $covered = 0;

        foreach ($plated as $group) {
            $value = round($grams[$group->value] ?? 0, 0);
            $isCovered = $value > 0;
            $covered += $isCovered ? 1 : 0;

            $groups[] = [
                'group' => $group,
                'grams' => $value,
                'share' => $total > 0 ? round($value / $total, 2) : 0.0,
                'target' => $targets[$group->value],
                'covered' => $isCovered,
                'items' => $this->itemsByGroup($meals)[$group->value] ?? 0,
            ];
        }

        $missing = collect($groups)->where('covered', false)->map(fn ($g) => $g['group'])->values()->all();

        return [
            'groups' => $groups,
            'covered' => $covered,
            'total' => count($plated),
            'is_balanced' => $covered === count($plated),
            'missing' => $missing,
            'headline' => $this->headline($covered, $missing),
        ];
    }

    /**
     * Jumlah hari dalam satu periode yang piringnya lengkap.
     * Dipakai di halaman laporan sebagai ukuran keseimbangan, bukan kalori.
     *
     * @param  Collection<int, array{date: CarbonInterface}>  $series
     * @return array{days: int, balanced: int, rate: int}
     */
    public function balanceOverPeriod(User $user, Collection $series): array
    {
        $days = $series->count();
        $balanced = 0;

        foreach ($series as $day) {
            if ($this->forDate($user, $day['date'])['is_balanced']) {
                $balanced++;
            }
        }

        return [
            'days' => $days,
            'balanced' => $balanced,
            'rate' => $days > 0 ? (int) round($balanced / $days * 100) : 0,
        ];
    }

    /**
     * Berat per kelompok dari semua item satu hari.
     *
     * @param  Collection<int, Meal>  $meals
     * @return array<string, float>
     */
    private function gramsByGroup(Collection $meals): array
    {
        $grams = [];

        foreach ($this->classify($meals) as $row) {
            $grams[$row['group']->value] = ($grams[$row['group']->value] ?? 0) + $row['weight'];
        }

        return $grams;
    }

    /**
     * @param  Collection<int, Meal>  $meals
     * @return array<string, int>
     */
    private function itemsByGroup(Collection $meals): array
    {
        $counts = [];

        foreach ($this->classify($meals) as $row) {
            $counts[$row['group']->value] = ($counts[$row['group']->value] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * Tentukan kelompok tiap item.
     *
     * Satu aturan untuk semua jalur: hasil scan dan catatan manual sama-sama
     * memakai nama makanan. Kalau memakai kategori database, "Sambal" akan
     * terhitung sebagai satu porsi sayur, padahal sambal hanya bumbu.
     *
     * @param  Collection<int, Meal>  $meals
     * @return list<array{group: FoodGroup, weight: float}>
     */
    private function classify(Collection $meals): array
    {
        $rows = [];

        foreach ($meals->flatMap->items as $item) {
            // Berat kosong dianggap satu porsi wajar supaya tidak hilang dari hitungan.
            $weight = (float) ($item->weight_g ?: 100);

            $rows[] = ['group' => FoodGroup::guess($item->name), 'weight' => $weight];
        }

        return $rows;
    }

    /**
     * @param  list<FoodGroup>  $missing
     */
    private function headline(int $covered, array $missing): string
    {
        if ($covered === 0) {
            return 'Piring masih kosong. Mulai dari satu makanan pokok.';
        }

        if ($missing === []) {
            return 'Piring hari ini sudah lengkap empat kelompoknya.';
        }

        $names = collect($missing)->map(fn (FoodGroup $g) => mb_strtolower($g->short()))->implode(', ');

        return $covered >= 3
            ? "Hampir lengkap. Tinggal tambah {$names}."
            : "Piringmu baru terisi {$covered} dari 4 kelompok. Kurang {$names}.";
    }
}
