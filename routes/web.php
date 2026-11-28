<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FoodSearchController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\MealController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\WaterLogController;
use App\Http\Controllers\WeightLogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Halaman depan dan login
|--------------------------------------------------------------------------
| Pemilik: ketua tim. Setiap blok di bawah dipisah per fitur supaya
| anggota tim hanya mengubah blok miliknya dan konflik merge jarang terjadi.
*/

Route::get('/', LandingController::class)->name('landing');
Route::view('/privasi', 'legal.privacy')->name('privacy');

Route::middleware('guest')->group(function () {
    Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');

    // Masuk dan daftar dengan email dan kata sandi.
    Route::get('/masuk', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/masuk', [AuthController::class, 'login'])->name('login.store');
    Route::get('/daftar', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/daftar', [AuthController::class, 'register'])->name('register.store');

    // Lupa kata sandi.
    Route::get('/lupa-sandi', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/lupa-sandi', [PasswordResetController::class, 'sendLink'])->name('password.email');
    Route::get('/atur-sandi/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/atur-sandi', [PasswordResetController::class, 'reset'])->name('password.update');
});

Route::post('/logout', [GoogleController::class, 'logout'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Onboarding
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'not-onboarded'])->group(function () {
    Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
    Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
});

Route::middleware(['auth', 'onboarded'])->group(function () {

    /*
    |----------------------------------------------------------------------
    | Dashboard
    |----------------------------------------------------------------------
    */

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    /*
    |----------------------------------------------------------------------
    | Scan gizi
    |----------------------------------------------------------------------
    */

    Route::get('/scan', [ScanController::class, 'create'])->name('scan.create');
    Route::post('/scan', [ScanController::class, 'store'])->middleware('throttle:ai')->name('scan.store');
    Route::get('/scan/{scan}', [ScanController::class, 'show'])->name('scan.show');
    Route::post('/scan/{scan}/retry', [ScanController::class, 'retry'])->middleware('throttle:ai')->name('scan.retry');
    Route::post('/scan/{scan}/meal', [ScanController::class, 'saveMeal'])->name('scan.meal');
    Route::get('/media/scans/{scan}', [ScanController::class, 'photo'])->name('scan.photo');

    /*
    |----------------------------------------------------------------------
    | Catatan makan dan daftar makanan
    |----------------------------------------------------------------------
    */

    Route::get('/meals', [MealController::class, 'index'])->name('meals.index');
    Route::get('/meals/create', [MealController::class, 'create'])->name('meals.create');
    Route::post('/meals', [MealController::class, 'store'])->name('meals.store');
    Route::get('/meals/{meal}/edit', [MealController::class, 'edit'])->name('meals.edit');
    Route::put('/meals/{meal}', [MealController::class, 'update'])->name('meals.update');
    Route::delete('/meals/{meal}', [MealController::class, 'destroy'])->name('meals.destroy');
    Route::post('/meals/{meal}/repeat', [MealController::class, 'repeat'])->name('meals.repeat');
    Route::get('/foods/search', FoodSearchController::class)->name('foods.search');

    /*
    |----------------------------------------------------------------------
    | Chatbot gizi
    |----------------------------------------------------------------------
    */

    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat', [ChatController::class, 'store'])->middleware('throttle:ai')->name('chat.store');
    Route::get('/chat/{conversation}', [ChatController::class, 'show'])->name('chat.show');
    Route::post('/chat/{conversation}/messages', [ChatController::class, 'send'])->middleware('throttle:ai')->name('chat.send');
    Route::post('/chat/{conversation}/retry', [ChatController::class, 'retry'])->middleware('throttle:ai')->name('chat.retry');
    Route::delete('/chat/{conversation}', [ChatController::class, 'destroy'])->name('chat.destroy');

    /*
    |----------------------------------------------------------------------
    | Laporan, berat badan, air
    |----------------------------------------------------------------------
    */

    Route::get('/reports', ReportController::class)->name('reports.index');
    Route::post('/reports/ai', [ReportController::class, 'generate'])->middleware('throttle:ai')->name('reports.ai');
    Route::get('/weight', [WeightLogController::class, 'index'])->name('weight.index');
    Route::post('/weight', [WeightLogController::class, 'store'])->name('weight.store');
    Route::delete('/weight/{weightLog}', [WeightLogController::class, 'destroy'])->name('weight.destroy');
    Route::post('/water', [WaterLogController::class, 'store'])->name('water.store');
    Route::delete('/water/latest', [WaterLogController::class, 'destroyLatest'])->name('water.destroy-latest');

    /*
    |----------------------------------------------------------------------
    | Profil dan pengaturan
    |----------------------------------------------------------------------
    */

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/targets', [ProfileController::class, 'updateTargets'])->name('profile.targets');
    Route::delete('/profile/targets', [ProfileController::class, 'resetTargets'])->name('profile.targets.reset');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Panel admin
|--------------------------------------------------------------------------
| Admin tidak melewati onboarding dan tidak memakai halaman pengguna.
| Semua rute di sini langsung menuju halaman yang ditangani admin.
*/

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');
    Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
    Route::get('/ai-usage', Admin\AiUsageController::class)->name('ai-usage');
    Route::resource('foods', Admin\FoodController::class)->except('show');
});
