<?php

namespace App\Services;

use App\Ai\Agents\ReportAnalyst;
use App\Ai\FakeResponses;
use App\Exceptions\AiFailure;
use App\Models\NutritionReport;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Ai\Exceptions\ProviderConnectionException;
use Throwable;

/**
 * Menyiapkan data laporan dan meminta analisis AI satu kali per
 * kombinasi periode + data. Hasilnya disimpan supaya halaman laporan
 * tidak memanggil AI berulang kali.
 */
class ReportService
{
    public function __construct(
        private readonly DailySummaryService $summary,
        private readonly PlateBalance $plate,
        private readonly NutritionCalculator $calculator,
        private readonly AiUsageLimiter $limiter,
    ) {}

    /**
     * Data untuk halaman laporan, termasuk laporan AI terbaru bila ada.
     */
    public function page(User $user, int $period): array
    {
        $built = $this->build($user, $period);
        $report = $this->freshReport($user, $period, $built['fingerprint']);

        return [
            ...$built['view'],
            'report' => $report,
            'needs_report' => ! $report && $built['view']['stats']['logged_days'] > 0,
        ];
    }

    /**
     * Buat atau ambil laporan AI untuk periode ini.
     */
    public function generate(User $user, int $period): NutritionReport
    {
        $built = $this->build($user, $period);

        if ($built['view']['stats']['logged_days'] === 0) {
            throw new AiFailure('Belum ada catatan makan pada periode ini. Catat beberapa hari dulu, lalu buka laporan lagi.', 'empty');
        }

        $cached = $this->freshReport($user, $period, $built['fingerprint']);

        if ($cached) {
            return $cached;
        }

        $this->limiter->ensureAvailable($user, 'insight');

        $fake = (bool) config('gizisnap.ai.fake');
        $model = $fake ? 'fake' : (string) config('gizisnap.ai.report_model');
        $started = microtime(true);
        $usage = null;
        $today = today();
        $key = [
            'user_id' => $user->id,
            'period_days' => $period,
            'period_end' => $today->toDateString(),
        ];

        try {
            if ($fake) {
                $raw = FakeResponses::report($period, $built['view']['stats']);
            } else {
                $agent = new ReportAnalyst($built['context'], $period);

                $response = retry(2, fn () => $agent->prompt(
                    'Buat laporan pola makan saya untuk periode ini.',
                    provider: config('gizisnap.ai.provider'),
                    model: $model,
                    timeout: (int) config('gizisnap.ai.timeout'),
                ), 500, fn (Throwable $e) => $e instanceof ProviderConnectionException);

                $usage = $response->usage;
                $raw = $response->toArray();
            }

            $content = $this->normalize($raw);
        } catch (Throwable $e) {
            $failure = $e instanceof AiFailure ? $e : AiFailure::fromThrowable($e);

            Log::warning('Laporan AI gagal', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            $this->limiter->record($user, 'insight', $model, $usage, $this->elapsed($started), 'error', $failure->reason);

            NutritionReport::updateOrCreate($key, [
                'period_start' => $today->copy()->subDays($period - 1)->toDateString(),
                'fingerprint' => $built['fingerprint'],
                'status' => 'failed',
                'content' => null,
                'model' => $model,
                'error_message' => $failure->getMessage(),
                'generated_at' => now(),
            ]);

            throw $failure;
        }

        $this->limiter->record($user, 'insight', $model, $usage, $this->elapsed($started), 'success');

        return NutritionReport::updateOrCreate($key, [
            'period_start' => $today->copy()->subDays($period - 1)->toDateString(),
            'fingerprint' => $built['fingerprint'],
            'status' => 'ready',
            'content' => $content,
            'model' => $model,
            'error_message' => null,
            'generated_at' => now(),
        ]);
    }

    private function freshReport(User $user, int $period, string $fingerprint): ?NutritionReport
    {
        $report = NutritionReport::query()
            ->where('user_id', $user->id)
            ->where('period_days', $period)
            ->whereDate('period_end', today()->toDateString())
            ->where('status', 'ready')
            ->latest('id')
            ->first();

        return $report && $report->fingerprint === $fingerprint && $report->isReady()
            ? $report
            : null;
    }

    /**
     * @return array{view: array<string, mixed>, context: string, fingerprint: string}
     */
    private function build(User $user, int $period): array
    {
        $series = $this->summary->series($user, $period);
        $stats = $this->summary->periodStats($series);
        $balance = $this->plate->balanceOverPeriod($user, $series);
        $target = $user->targetFor(now());
        $profile = $user->healthProfile;

        $start = now()->subDays($period - 1)->startOfDay();
        $end = now()->endOfDay();

        $meals = $user->meals()
            ->whereBetween('eaten_at', [$start, $end])
            ->get();

        $topFoods = $user->meals()
            ->where('eaten_at', '>=', $start)
            ->join('meal_items', 'meal_items.meal_id', '=', 'meals.id')
            ->selectRaw('meal_items.name, COUNT(*) as times, SUM(meal_items.calories_kcal) as calories')
            ->groupBy('meal_items.name')
            ->orderByDesc('times')
            ->limit(5)
            ->get();

        // Berat badan ditampilkan di sini juga supaya tidak jadi halaman terpisah.
        $weightLogs = $user->weightLogs()->orderByDesc('logged_on')->take(60)->get();
        $latestWeight = $weightLogs->first();
        $firstWeight = $weightLogs->last();
        $bmi = $latestWeight ? $this->calculator->bmi($latestWeight->weight_kg, $profile->height_cm) : null;
        $weightChartLogs = $weightLogs->take(30)->reverse()->values();
        $weightChange = $latestWeight && $firstWeight && $latestWeight->id !== $firstWeight->id
            ? round($latestWeight->weight_kg - $firstWeight->weight_kg, 1)
            : null;

        $loggedDays = max(1, $stats['logged_days']);
        $nutrient = [
            'fiber_g' => round($meals->sum('total_fiber_g') / $loggedDays, 1),
            'sugar_g' => round($meals->sum('total_sugar_g') / $loggedDays, 1),
            'sodium_mg' => round($meals->sum('total_sodium_mg') / $loggedDays, 1),
        ];
        $waterMl = (int) $user->waterLogs()->whereBetween('logged_at', [$start, $end])->sum('amount_ml');
        $waterAvg = (int) round($waterMl / max(1, $stats['days']));

        $chart = [
            'labels' => $series->map(fn ($d) => $d['date']->translatedFormat($period === 7 ? 'D' : 'j M'))->values(),
            'calories' => $series->pluck('calories_kcal')->values(),
            'targets' => $series->pluck('target_kcal')->values(),
            'protein' => $series->pluck('protein_g')->values(),
            'carbs' => $series->pluck('carbs_g')->values(),
            'fat' => $series->pluck('fat_g')->values(),
        ];

        $view = [
            'series' => $series,
            'stats' => $stats,
            'balance' => $balance,
            'target' => $target,
            'topFoods' => $topFoods,
            'weightLogs' => $weightLogs,
            'latestWeight' => $latestWeight,
            'firstWeight' => $firstWeight,
            'weightChange' => $weightChange,
            'bmi' => $bmi,
            'bmiCategory' => $bmi ? $this->calculator->bmiCategory($bmi, $profile->age()) : null,
            'weightChart' => [
                'labels' => $weightChartLogs->map(fn ($l) => $l->logged_on->translatedFormat('j M')),
                'values' => $weightChartLogs->pluck('weight_kg'),
            ],
            'chart' => $chart,
        ];

        $context = [
            'period' => $period,
            'range' => $series->first()['date']->toDateString().' sampai '.$series->last()['date']->toDateString(),
            'stats' => $stats,
            'balance' => $balance,
            'nutrient' => $nutrient,
            'water_avg_ml' => $waterAvg,
            'target' => $target?->only(['calories_kcal', 'protein_g', 'carbs_g', 'fat_g', 'water_ml']),
            'profile' => $profile?->only(['gender', 'birth_date', 'height_cm', 'weight_kg', 'activity_level', 'goal', 'allergies', 'dietary_preference', 'health_conditions']),
            'top_foods' => $topFoods->map(fn ($food) => ['name' => $food->name, 'times' => (int) $food->times])->all(),
            'weight' => [
                'latest' => $latestWeight?->weight_kg,
                'change' => $weightChange,
                'bmi' => $bmi,
            ],
        ];

        return [
            'view' => $view,
            'context' => $this->contextText($user, $period, $view, $nutrient, $waterAvg),
            'fingerprint' => hash('sha256', json_encode($context, JSON_UNESCAPED_UNICODE)),
        ];
    }

    private function contextText(User $user, int $period, array $view, array $nutrient, int $waterAvg): string
    {
        $stats = $view['stats'];
        $balance = $view['balance'];
        $target = $view['target'];
        $profile = $user->healthProfile;
        $lines = [];

        $lines[] = 'Periode: '.$period.' hari terakhir ('.$view['series']->first()['date']->translatedFormat('j M Y').' sampai '.$view['series']->last()['date']->translatedFormat('j M Y').').';
        $lines[] = 'Nama panggilan: '.$user->firstName();

        if ($profile) {
            $lines[] = sprintf(
                'Profil: %s, %d tahun, tinggi %s cm, berat %s kg, aktivitas %s, tujuan %s.',
                $profile->gender->label(), $profile->age(), $profile->height_cm, $profile->weight_kg,
                mb_strtolower($profile->activity_level->label()), mb_strtolower($profile->goal->label()),
            );

            if ($profile->health_conditions) {
                $lines[] = 'Kondisi kesehatan yang diisi pengguna: '.$profile->health_conditions.'.';
            }

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

        $lines[] = sprintf(
            'Hari tercatat: %d dari %d hari. Rata-rata kalori: %s kkal. Protein %s g, karbohidrat %s g, lemak %s g.',
            $stats['logged_days'], $stats['days'], $stats['avg_calories'], $stats['avg_protein'], $stats['avg_carbs'], $stats['avg_fat'],
        );
        $lines[] = sprintf(
            'Hari dalam rentang target: %d. Piring seimbang: %d dari %d hari. Rata-rata per hari: serat %s g, gula %s g, natrium %s mg, air %d ml.',
            $stats['within_target_days'], $balance['balanced'], $balance['days'], $nutrient['fiber_g'], $nutrient['sugar_g'], $nutrient['sodium_mg'], $waterAvg,
        );

        if ($view['topFoods']->isNotEmpty()) {
            $foods = $view['topFoods']->map(fn ($food) => $food->name.' ('.$food->times.'x)')->implode(', ');
            $lines[] = 'Makanan paling sering: '.$foods.'.';
        } else {
            $lines[] = 'Makanan paling sering: belum ada data.';
        }

        if ($view['latestWeight']) {
            $weight = 'Berat terakhir: '.$view['latestWeight']->weight_kg.' kg.';
            if ($view['weightChange'] !== null) {
                $weight .= ' Perubahan selama periode: '.($view['weightChange'] > 0 ? '+' : '').$view['weightChange'].' kg.';
            }
            $lines[] = $weight;
        } else {
            $lines[] = 'Berat badan: belum ada catatan.';
        }

        $lines[] = 'Catatan: angka rata-rata hanya dihitung dari hari yang punya catatan makan.';

        return implode("\n", $lines);
    }

    private function normalize(array $raw): array
    {
        $clean = fn ($value, int $max) => Str::limit(trim((string) $value), $max);

        $headline = $clean($raw['headline'] ?? '', 120);
        $summary = $clean($raw['summary'] ?? '', 800);

        if ($headline === '' || $summary === '') {
            throw new AiFailure('Laporan AI kosong. Coba lagi.', 'invalid_schema');
        }

        $changes = collect($raw['changes'] ?? [])
            ->filter(fn ($item) => is_array($item) && trim((string) ($item['title'] ?? '')) !== '')
            ->map(fn ($item) => [
                'title' => $clean($item['title'], 80),
                'detail' => $clean($item['detail'] ?? '', 300),
            ])
            ->take(4)
            ->values()
            ->all();

        $list = fn (string $key, int $max) => collect($raw[$key] ?? [])
            ->filter(fn ($item) => is_string($item) && trim($item) !== '')
            ->map(fn ($item) => $clean($item, 200))
            ->take($max)
            ->values()
            ->all();

        return [
            'headline' => $headline,
            'summary' => $summary,
            'changes' => $changes,
            'body' => $list('body', 4),
            'reduce' => $list('reduce', 5),
            'increase' => $list('increase', 5),
            'encouragement' => $clean($raw['encouragement'] ?? '', 240),
        ];
    }

    private function elapsed(float $started): int
    {
        return (int) round((microtime(true) - $started) * 1000);
    }
}
