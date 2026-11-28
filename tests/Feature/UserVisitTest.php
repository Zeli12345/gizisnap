<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use App\Models\UserVisit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UserVisitTest extends TestCase
{
    use RefreshDatabase;

    public function test_hari_yang_sama_hanya_dihitung_sekali(): void
    {
        $user = User::create([
            'name' => 'Pengguna',
            'email' => 'pengguna@example.com',
            'password' => 'password123',
            'role' => Role::User,
        ]);

        UserVisit::recordFor($user, Carbon::parse('2026-11-23'));
        UserVisit::recordFor($user, Carbon::parse('2026-11-23'));
        UserVisit::recordFor($user, Carbon::parse('2026-11-24'));
        UserVisit::recordFor($user, Carbon::parse('2026-11-25'));

        $this->assertSame(3, UserVisit::where('user_id', $user->id)->count());
    }

    public function test_ringkasan_admin_menampilkan_indikator_kunjungan_beranda(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'role' => Role::Admin,
        ]);

        $user = User::create([
            'name' => 'Pengguna',
            'email' => 'pengguna@example.com',
            'password' => 'password123',
            'role' => Role::User,
        ]);

        UserVisit::recordFor($user, today());
        UserVisit::recordFor($user, today()->subDay());

        $this->actingAs($admin)->get('/admin')
            ->assertOk()
            ->assertSee('Kunjungan beranda')
            ->assertSee('2');
    }
}
