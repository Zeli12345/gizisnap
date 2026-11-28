<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_kebijakan_catatan_makan(): void
    {
        $owner = $this->onboardedUser();
        $other = $this->onboardedUser();
        $admin = $this->adminUser();
        $meal = $this->makeMeal($owner);

        $this->assertTrue($owner->can('view', $meal));
        $this->assertTrue($owner->can('update', $meal));
        $this->assertTrue($owner->can('delete', $meal));

        $this->assertFalse($other->can('view', $meal));
        $this->assertFalse($other->can('update', $meal));
        $this->assertFalse($other->can('delete', $meal));

        // Admin pun tidak otomatis boleh membuka data pribadi pengguna.
        $this->assertFalse($admin->can('view', $meal));
    }

    public function test_kebijakan_scan(): void
    {
        $owner = $this->onboardedUser();
        $other = $this->onboardedUser();
        $scan = $this->makeScan($owner);

        $this->assertTrue($owner->can('view', $scan));
        $this->assertTrue($owner->can('update', $scan));
        $this->assertTrue($owner->can('delete', $scan));

        $this->assertFalse($other->can('view', $scan));
        $this->assertFalse($other->can('update', $scan));
        $this->assertFalse($other->can('delete', $scan));
    }

    public function test_kebijakan_percakapan_chat(): void
    {
        $owner = $this->onboardedUser();
        $other = User::factory()->create();
        $conversation = $this->makeConversation($owner);

        $this->assertTrue($owner->can('view', $conversation));
        $this->assertTrue($owner->can('update', $conversation));
        $this->assertTrue($owner->can('delete', $conversation));

        $this->assertFalse($other->can('view', $conversation));
        $this->assertFalse($other->can('update', $conversation));
        $this->assertFalse($other->can('delete', $conversation));
    }
}
