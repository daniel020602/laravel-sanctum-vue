<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Sub;
use App\Models\Week;
use App\Models\Menu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $soup;
    protected $menuIds;
    protected $week;

    protected function setUp(): void
    {
        parent::setUp();

        $this->soup = Menu::factory()->create(['type' => 'soup']);
        $this->menuIds = Menu::factory()->count(3)->create(['type' => 'main'])->pluck('id')->toArray();

        $this->week = Week::factory()->create([
            'week' => now()->weekOfYear,
            'soup' => $this->soup->id,
            'day1a' => $this->menuIds[0],
            'day1b' => $this->menuIds[1],
            'day2a' => $this->menuIds[2],
            'day2b' => $this->menuIds[0],
            'day3a' => $this->menuIds[1],
            'day3b' => $this->menuIds[2],
            'day4a' => $this->menuIds[0],
            'day4b' => $this->menuIds[1],
            'day5a' => $this->menuIds[2],
            'day5b' => $this->menuIds[0],
        ]);
    }
    protected function createSub($user)
    {
        return Sub::factory()->create([
            'user_id' => $user,
            'week_id' => $this->week->id,
            'day1' => $this->week->day1a,
            'day2' => $this->week->day2a,
            'day3' => $this->week->day3a,
            'day4' => $this->week->day4a,
            'day5' => $this->week->day5a,
        ]);
    }

    public function test_non_admin_can_create_own_subscription()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user, 'sanctum');

        $payload = [
            'week_id' => $this->week->id,
            'day1' => $this->week->day1a,
            'day2' => $this->week->day2a,
            'day3' => $this->week->day3a,
            'day4' => $this->week->day4a,
            'day5' => $this->week->day5a,
        ];

        $response = $this->postJson('/api/subs', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('subs', ['user_id' => $user->id, 'week_id' => $this->week->id]);
    }


    public function test_guest_cannot_create_subscription()
    {
        $response = $this->postJson('/api/subs', [
            'week_id' => $this->week->id,
            'notes' => 'Gluten-free',
        ]);

        $response->assertStatus(401);
    }

    public function test_user_can_update_own_subscription()
    {   
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');
        $sub = $this->createSub($user->id);
        $response = $this->putJson("/api/subs/{$sub->id}", [
            'day1' => $this->week->day1b,
            'day2' => $this->week->day2b,
            'day3' => $this->week->day3b,
            'day4' => $this->week->day4b,
            'day5' => $this->week->day5b,
        ]);

        $response->assertStatus(200);

    }

    public function test_user_cannot_update_others_subscription()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $sub = $this->createSub($user->id);
        $this->actingAs($otherUser, 'sanctum');

        $response = $this->putJson("/api/subs/{$sub->id}", [
            'notes' => 'Trying to update',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_view_all_subs()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_admin' => false]);
        $sub = $this->createSub($user->id);
        $this->actingAs($admin, 'sanctum');

        $response = $this->getJson('/api/subs');

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Subscriptions retrieved successfully']);

        $this->assertCount(1, $response->json('data'));
    }

    public function test_user_can_only_view_their_own_subs()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $sub = $this->createSub($user->id);
        $otherUser = User::factory()->create(['is_admin' => false]);
        $otherSub = $this->createSub($otherUser->id);
        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/subs');

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Subscriptions retrieved successfully']);

        $this->assertCount(1, $response->json('data'));
    }

    public function test_user_cannot_view_others_subscription()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $sub = $this->createSub($user->id);
        $this->actingAs($otherUser, 'sanctum');

        $response = $this->getJson("/api/subs/{$sub->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_delete_own_subscription()
    {
        $user = User::factory()->create();
        $sub = $this->createSub($user->id);
        $this->actingAs($user, 'sanctum');

        $response = $this->deleteJson("/api/subs/{$sub->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('subs', ['id' => $sub->id]);
    }

    public function test_user_cannot_delete_others_subscription()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $sub = $this->createSub($user->id);
        $this->actingAs($otherUser, 'sanctum');

        $response = $this->deleteJson("/api/subs/{$sub->id}");

        $response->assertStatus(403);
    }
}
