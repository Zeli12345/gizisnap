<?php

namespace Tests;

use App\Enums\MealType;
use App\Models\ChatConversation;
use App\Models\Food;
use App\Models\HealthProfile;
use App\Models\Meal;
use App\Models\Scan;
use App\Models\User;
use App\Services\MealService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;

abstract class TestCase extends BaseTestCase
{
    /**
     * Pengguna dengan profil kesehatan, target harian, dan catatan berat.
     * Inilah bentuk akun yang sudah melewati onboarding.
     */
    protected function onboardedUser(array $user = [], array $profile = []): User
    {
        $user = User::factory()->create(array_merge([
            'onboarded_at' => now(),
        ], $user));

        $user->healthProfile()->create(array_merge([
            'gender' => 'male',
            'birth_date' => now()->subYears(20)->toDateString(),
            'height_cm' => 170,
            'weight_kg' => 65,
            'activity_level' => 'moderate',
            'goal' => 'maintain',
            'allergies' => null,
            'dietary_preference' => null,
            'health_conditions' => null,
        ], $profile));

        $user->dailyTargets()->create([
            'calories_kcal' => 2000,
            'protein_g' => 100,
            'carbs_g' => 275,
            'fat_g' => 55.6,
            'water_ml' => 2000,
            'source' => 'auto',
            'effective_from' => today()->toDateString(),
        ]);

        $user->weightLogs()->create([
            'weight_kg' => 65,
            'logged_on' => today()->toDateString(),
        ]);

        return $user->fresh();
    }

    protected function adminUser(array $attributes = []): User
    {
        return User::factory()->admin()->create($attributes);
    }

    protected function makeFood(array $attributes = []): Food
    {
        return Food::create(array_merge([
            'name' => 'Nasi putih',
            'category' => 'Makanan pokok',
            'serving_desc' => '1 porsi (200 g)',
            'serving_weight_g' => 200,
            'calories_kcal' => 360,
            'protein_g' => 6.8,
            'carbs_g' => 79.2,
            'fat_g' => 0.6,
            'fiber_g' => 0.8,
            'sugar_g' => 0.2,
            'sodium_mg' => 2,
            'source' => 'TKPI Kemenkes',
            'is_active' => true,
        ], $attributes));
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    protected function makeMeal(User $user, array $attributes = [], array $items = []): Meal
    {
        $items = $items ?: [[
            'name' => 'Nasi putih',
            'portion_desc' => '1 porsi',
            'weight_g' => 200,
            'calories_kcal' => 360,
            'protein_g' => 6.8,
            'carbs_g' => 79.2,
            'fat_g' => 0.6,
            'fiber_g' => 0.8,
            'sugar_g' => 0.2,
            'sodium_mg' => 2,
        ]];

        $meal = app(MealService::class)->create($user, array_merge([
            'meal_type' => MealType::Lunch,
            'eaten_at' => now(),
            'title' => 'Nasi putih',
            'source' => 'manual',
            'notes' => null,
        ], $attributes), $items);

        return $meal;
    }

    protected function makeScan(User $user, array $attributes = []): Scan
    {
        return Scan::create(array_merge([
            'user_id' => $user->id,
            'image_path' => 'scans/'.$user->id.'/uji.jpg',
            'status' => 'completed',
            'dish_name' => 'Nasi goreng telur',
            'confidence' => 0.86,
            'ai_result' => [
                'is_food' => true,
                'dish_name' => 'Nasi goreng telur',
                'confidence' => 0.86,
                'items' => [[
                    'name' => 'Nasi goreng',
                    'portion_desc' => '1 piring',
                    'weight_g' => 250,
                    'calories_kcal' => 418,
                    'protein_g' => 10,
                    'carbs_g' => 60,
                    'fat_g' => 15,
                ]],
                'notes' => '',
            ],
            'error_message' => null,
        ], $attributes));
    }

    protected function makeConversation(User $user, array $attributes = []): ChatConversation
    {
        return $user->chatConversations()->create(array_merge([
            'title' => 'Percakapan uji',
            'last_message_at' => now(),
        ], $attributes));
    }

    /**
     * JPEG 1x1 yang sah, dipakai agar aturan validasi "image" lolos
     * tanpa membutuhkan ekstensi GD saat menjalankan tes.
     */
    protected function fakeJpeg(string $name = 'makanan.jpg'): UploadedFile
    {
        $jpeg = base64_decode(
            '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAX/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAF//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABBQJ//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPwF//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPwF//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQAGPwJ//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPyF//9oADAMBAAIAAwAAABD/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAEDAQE/EB//xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAECAQE/EB//xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAE/EB//2Q==',
            true
        );

        return UploadedFile::fake()->createWithContent($name, $jpeg);
    }

    protected function assertProfileShape(HealthProfile $profile): void
    {
        $this->assertNotNull($profile->gender);
        $this->assertNotNull($profile->activity_level);
        $this->assertNotNull($profile->goal);
    }
}
