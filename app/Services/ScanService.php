<?php

namespace App\Services;

use App\Ai\Agents\FoodScanner;
use App\Ai\FakeResponses;
use App\Enums\MealType;
use App\Enums\ScanStatus;
use App\Exceptions\AiFailure;
use App\Models\Meal;
use App\Models\Scan;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use Laravel\Ai\Exceptions\ProviderConnectionException;
use Laravel\Ai\Files\Image as AiImage;
use Throwable;

class ScanService
{
    public function __construct(
        private readonly AiUsageLimiter $limiter,
        private readonly NutritionCalculator $calculator,
        private readonly MealService $meals,
        private readonly FoodCatalog $foods,
    ) {}

    /**
     * Simpan foto (dikecilkan), buat baris scan, lalu analisis.
     */
    public function createAndAnalyze(User $user, UploadedFile $photo): Scan
    {
        $this->limiter->ensureAvailable($user, 'scan');

        $scan = $user->scans()->create([
            'image_path' => $this->storePhoto($user, $photo),
            'status' => ScanStatus::Pending,
        ]);

        return $this->analyze($scan, $photo->getClientOriginalName());
    }

    /**
     * Kirim foto ke AI dan simpan hasilnya. Tidak melempar error
     * kegagalan AI; status scan yang mencatatnya.
     */
    public function analyze(Scan $scan, ?string $originalName = null): Scan
    {
        $user = $scan->user;
        $this->limiter->ensureAvailable($user, 'scan');

        $healthConditions = $user->healthProfile?->health_conditions;

        $fake = (bool) config('gizisnap.ai.fake');
        $model = $fake ? 'fake' : (string) config('gizisnap.ai.scan_model');
        $started = microtime(true);
        $usage = null;

        try {
            if ($fake) {
                $raw = FakeResponses::scan($originalName);
            } else {
                $response = retry(2, fn () => FoodScanner::make($healthConditions)->prompt(
                    FoodScanner::PROMPT,
                    attachments: [AiImage::fromStorage($scan->image_path, config('gizisnap.scan.disk'))],
                    provider: config('gizisnap.ai.provider'),
                    model: $model,
                    timeout: (int) config('gizisnap.ai.timeout'),
                ), 500, fn (Throwable $e) => $e instanceof ProviderConnectionException);

                $usage = $response->usage;
                $raw = $response->toArray();
            }

            $result = $this->normalize($raw);
        } catch (Throwable $e) {
            $failure = $e instanceof AiFailure ? $e : AiFailure::fromThrowable($e);

            Log::warning('Scan gagal', ['scan_id' => $scan->id, 'error' => $e->getMessage()]);
            $this->limiter->record($user, 'scan', $model, $usage, $this->elapsed($started), 'error', $failure->reason);

            $scan->update([
                'status' => ScanStatus::Failed,
                'error_message' => $failure->getMessage(),
            ]);

            return $scan;
        }

        $this->limiter->record($user, 'scan', $model, $usage, $this->elapsed($started), 'success');

        $isFood = $result['is_food'] && $result['items'] !== [];

        $scan->update([
            'status' => $isFood ? ScanStatus::Completed : ScanStatus::NotFood,
            'dish_name' => $isFood ? $result['dish_name'] : null,
            'confidence' => $result['confidence'],
            'ai_result' => $result,
            'error_message' => null,
        ]);

        if ($isFood) {
            try {
                $this->foods->addFromScan($result['items']);
            } catch (Throwable $e) {
                // Katalog makanan tidak boleh menggagalkan hasil scan pengguna.
                Log::warning('Gagal menambah makanan hasil scan ke katalog', [
                    'scan_id' => $scan->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $scan;
    }

    /**
     * Simpan hasil scan (yang mungkin sudah dikoreksi pengguna) menjadi catatan makan.
     *
     * @param  list<array<string, mixed>>  $items
     */
    public function saveAsMeal(Scan $scan, MealType $type, CarbonInterface $eatenAt, string $title, array $items, ?string $notes = null): Meal
    {
        $original = collect($scan->items());

        $items = collect($items)->map(function (array $item, int $index) use ($original) {
            $source = $original->get($index);
            $item['is_edited'] = $source === null || collect(['name', 'calories_kcal', 'protein_g', 'carbs_g', 'fat_g', 'portion_desc'])
                ->contains(fn ($key) => (string) ($source[$key] ?? '') !== (string) ($item[$key] ?? ''));

            return $item;
        })->all();

        return $this->meals->create($scan->user, [
            'scan_id' => $scan->id,
            'meal_type' => $type,
            'eaten_at' => $eatenAt,
            'title' => $title,
            'source' => 'scan',
            'notes' => $notes,
        ], $items);
    }

    /**
     * Periksa dan rapikan jawaban AI. Total tidak diminta dari AI.
     */
    public function normalize(array $raw): array
    {
        $raw = $this->unwrapStructuredPayload($raw);

        if (! array_key_exists('is_food', $raw) || ! isset($raw['items']) || ! is_array($raw['items'])) {
            throw new AiFailure('Jawaban AI tidak sesuai format. Coba lagi.', 'invalid_schema');
        }
        $num = fn ($value, float $max) => round(min($max, max(0, (float) $value)), 1);

        $items = collect($raw['items'])
            ->filter(fn ($item) => is_array($item) && trim((string) ($item['name'] ?? '')) !== '')
            ->take(15)
            ->map(function (array $item) use ($num) {
                $clean = [
                    'name' => Str::limit(trim((string) $item['name']), 117),
                    'portion_desc' => Str::limit(trim((string) ($item['portion_desc'] ?? '1 porsi')) ?: '1 porsi', 77),
                    'weight_g' => $num($item['weight_g'] ?? 0, 5000),
                    'calories_kcal' => $num($item['calories_kcal'] ?? 0, 5000),
                    'protein_g' => $num($item['protein_g'] ?? 0, 999),
                    'carbs_g' => $num($item['carbs_g'] ?? 0, 999),
                    'fat_g' => $num($item['fat_g'] ?? 0, 999),
                    'fiber_g' => $num($item['fiber_g'] ?? 0, 999),
                    'sugar_g' => $num($item['sugar_g'] ?? 0, 999),
                    'sodium_mg' => $num($item['sodium_mg'] ?? 0, 99999),
                ];

                $clean['needs_check'] = ! $this->calculator->caloriesLookConsistent(
                    $clean['calories_kcal'], $clean['protein_g'], $clean['carbs_g'], $clean['fat_g']);

                return $clean;
            })
            ->values()
            ->all();

        return [
            'is_food' => (bool) $raw['is_food'],
            'dish_name' => Str::limit(trim((string) ($raw['dish_name'] ?? '')) ?: ($items[0]['name'] ?? 'Makanan'), 147),
            'confidence' => round(min(1, max(0, (float) ($raw['confidence'] ?? 0))), 2),
            'items' => $items,
            'notes' => Str::limit(trim((string) ($raw['notes'] ?? '')), 500),
            'health_assessment' => $this->normalizeAssessment($raw['health_assessment'] ?? []),
        ];
    }

    /**
     * Rapikan penilaian kesehatan dari AI. Pengguna tanpa kondisi tetap
     * mendapat struktur kosong supaya halaman hasil scan tidak berubah.
     */
    private function normalizeAssessment(mixed $value): array
    {
        $data = is_array($value) ? $value : [];
        $severity = in_array($data['severity'] ?? null, ['safe', 'caution', 'danger'], true)
            ? $data['severity']
            : 'safe';

        $list = function (string $key, int $max) use ($data): array {
            $items = $data[$key] ?? [];

            if (! is_array($items)) {
                return [];
            }

            return collect($items)
                ->filter(fn ($item) => is_string($item) && trim($item) !== '')
                ->map(fn ($item) => Str::limit(trim($item), 160))
                ->take($max)
                ->values()
                ->all();
        };

        $flagged = $data['flagged_items'] ?? [];

        $flagged = is_array($flagged)
            ? collect($flagged)
                ->filter(fn ($item) => is_array($item) && trim((string) ($item['name'] ?? '')) !== '')
                ->map(fn ($item) => [
                    'name' => Str::limit(trim((string) $item['name']), 117),
                    'reason' => Str::limit(trim((string) ($item['reason'] ?? '')), 120),
                ])
                ->take(10)
                ->values()
                ->all()
            : [];

        return [
            'severity' => $severity,
            'summary' => Str::limit(trim((string) ($data['summary'] ?? '')), 400),
            'warnings' => $list('warnings', 4),
            'avoid' => $list('avoid', 5),
            'prefer' => $list('prefer', 5),
            'flagged_items' => $flagged,
        ];
    }

    /**
     * Sebagian penyedia membungkus jawaban dengan nama skema, misalnya
     * {"schema_definition": {...}}. Buka bungkusnya supaya kunci aslinya terbaca.
     */
    private function unwrapStructuredPayload(array $raw): array
    {
        if (array_key_exists('is_food', $raw) || count($raw) !== 1) {
            return $raw;
        }

        $inner = reset($raw);

        return is_array($inner) && array_key_exists('is_food', $inner) ? $inner : $raw;
    }

    private function storePhoto(User $user, UploadedFile $photo): string
    {
        $max = (int) config('gizisnap.scan.max_side_px');

        $encoded = Image::decodePath($photo->getRealPath())
            ->orient()
            ->scaleDown($max, $max)
            ->encodeUsingMediaType('image/jpeg', quality: 82);

        $path = config('gizisnap.scan.directory').'/'.$user->id.'/'.Str::uuid().'.jpg';
        Storage::disk(config('gizisnap.scan.disk'))->put($path, (string) $encoded);

        return $path;
    }

    private function elapsed(float $started): int
    {
        return (int) round((microtime(true) - $started) * 1000);
    }

    public static function defaultMealType(): MealType
    {
        return MealType::guessFromHour((int) now()->format('G'));
    }
}
