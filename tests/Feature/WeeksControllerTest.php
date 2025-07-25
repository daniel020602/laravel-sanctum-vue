<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Week;
use App\Models\Menu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class WeeksControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_create_week()
    {
        $response = $this->postJson('/api/weeks', [
            'week' => 30,
        ]);
        $response->assertStatus(401);
    }

    public function test_admin_can_create_week()
    {
        $soup = Menu::factory()->create(['type' => 'soup'])->id;
        $menuIds = Menu::factory()->count(3)->create(['type' => 'main'])->pluck('id')->toArray();
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/weeks', [
            'week' => 30,
            'soup' => $soup,
            'day1a' => $menuIds[1],
            'day1b' => $menuIds[2],
            'day2a' => $menuIds[0],
            'day2b' => $menuIds[1],
            'day3a' => $menuIds[2],
            'day3b' => $menuIds[0],
            'day4a' => $menuIds[1],
            'day4b' => $menuIds[2],
            'day5a' => $menuIds[2],
            'day5b' => $menuIds[1],
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['week' => 30]);
    }

    public function test_non_admin_cannot_create_week()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user, 'sanctum');
        $menuIds = Menu::factory()->count(3)->create(['type' => 'main'])->pluck('id')->toArray();
        $soup = Menu::factory()->create(['type' => 'soup'])->id;
        $response = $this->postJson('/api/weeks', [
            'week' => 30,
            'soup' => $soup,
            'day1a' => $menuIds[1],
            'day1b' => $menuIds[2],
            'day2a' => $menuIds[0],
            'day2b' => $menuIds[1],
            'day3a' => $menuIds[2],
            'day3b' => $menuIds[0],
            'day4a' => $menuIds[1],
            'day4b' => $menuIds[2],
            'day5a' => $menuIds[2],
            'day5b' => $menuIds[1],
        ]);
        $response->assertStatus(403);
    }

    public function test_admin_can_update_week()
    {
        $user = User::factory()->create(['is_admin' => true]);

        // Seed soup and menu items
        $menuIds = Menu::factory()->count(3)->create(['type' => 'main'])->pluck('id')->toArray();
        $soup = Menu::factory()->create(['type' => 'soup'])->id;

        // Create the original week
        $week = Week::factory()->create([
            'week' => 30,
            'soup' => $soup,
            'day1a' => $menuIds[1],
            'day1b' => $menuIds[2],
            'day2a' => $menuIds[0],
            'day2b' => $menuIds[1],
            'day3a' => $menuIds[2],
            'day3b' => $menuIds[0],
            'day4a' => $menuIds[1],
            'day4b' => $menuIds[2],
            'day5a' => $menuIds[2],
            'day5b' => $menuIds[1],
        ]);

        $this->actingAs($user, 'sanctum');

        // Full update with required fields
        $response = $this->putJson("/api/weeks/{$week->id}", [
            'week' => 31,
            'soup' => $soup,
            'day1a' => $menuIds[1],
            'day1b' => $menuIds[2],
            'day2a' => $menuIds[0],
            'day2b' => $menuIds[1],
            'day3a' => $menuIds[2],
            'day3b' => $menuIds[0],
            'day4a' => $menuIds[1],
            'day4b' => $menuIds[2],
            'day5a' => $menuIds[2],
            'day5b' => $menuIds[1],
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['week' => 31]);
    }


    public function test_non_admin_cannot_update_week()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $week = Week::factory()->create();
        $this->actingAs($user, 'sanctum');

        $response = $this->putJson("/api/weeks/{$week->id}", ['week' => 31]);
        $response->assertStatus(403);
    }

    public function test_admin_can_delete_week()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $week = Week::factory()->create();
        $this->actingAs($user, 'sanctum');

        $response = $this->deleteJson("/api/weeks/{$week->id}");
        $response->assertStatus(200);
        $this->assertDatabaseMissing('weeks', ['id' => $week->id]);
    }

    public function test_non_admin_cannot_delete_week()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $week = Week::factory()->create();
        $this->actingAs($user, 'sanctum');

        $response = $this->deleteJson("/api/weeks/{$week->id}");
        $response->assertStatus(403);
    }

    public function test_admin_can_view_all_weeks()
    {
        $user = User::factory()->create(['is_admin' => true]);
        Week::factory()->count(3)->create();
        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/weeks');
        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Weeks retrieved successfully']);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_non_admin_sees_only_current_and_future_weeks()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $now = Carbon::now();
        $this->actingAs($user, 'sanctum');
        $menuIds = Menu::factory()->count(3)->create(['type' => 'main'])->pluck('id')->toArray();
        $soup = Menu::factory()->create(['type' => 'soup'])->id;
        Week::factory()->create([
            'week' => $now->weekOfYear - 1, 
            'soup' => $soup,
            'day1a' => $menuIds[1],
            'day1b' => $menuIds[2],
            'day2a' => $menuIds[0],
            'day2b' => $menuIds[1],
            'day3a' => $menuIds[2],
            'day3b' => $menuIds[0],
            'day4a' => $menuIds[1],
            'day4b' => $menuIds[2],
            'day5a' => $menuIds[2],
            'day5b' => $menuIds[1],
        ]);
        Week::factory()->create(['week' => $now->weekOfYear,
            'soup' => $soup,
            'day1a' => $menuIds[1],
            'day1b' => $menuIds[2],
            'day2a' => $menuIds[0],
            'day2b' => $menuIds[1],
            'day3a' => $menuIds[2],
            'day3b' => $menuIds[0],
            'day4a' => $menuIds[1],
            'day4b' => $menuIds[2],
            'day5a' => $menuIds[2],
            'day5b' => $menuIds[1],
        ]);
        Week::factory()->create(['week' => $now->weekOfYear + 1,
            'soup' => $soup,
            'day1a' => $menuIds[1],
            'day1b' => $menuIds[2],
            'day2a' => $menuIds[0],
            'day2b' => $menuIds[1],
            'day3a' => $menuIds[2],
            'day3b' => $menuIds[0],
            'day4a' => $menuIds[1],
            'day4b' => $menuIds[2],
            'day5a' => $menuIds[2],
            'day5b' => $menuIds[1],
        ]);

        $response = $this->getJson('/api/weeks');
        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Weeks retrieved successfully']);

        $data = $response->json('data');
        $this->assertCount(2, $data); // only current and future
    }

    public function test_admin_can_view_past_week()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $pastWeek = Week::factory()->create(['week' => Carbon::now()->weekOfYear - 1]);
        $this->actingAs($user, 'sanctum');

        $response = $this->getJson("/api/weeks/{$pastWeek->id}");
        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Week retrieved successfully']);
    }

    public function test_non_admin_cannot_view_past_week()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $pastWeek = Week::factory()->create(['week' => Carbon::now()->weekOfYear - 1]);
        $this->actingAs($user, 'sanctum');

        $response = $this->getJson("/api/weeks/{$pastWeek->id}");
        $response->assertStatus(403)
            ->assertJsonFragment(['message' => 'You cannot view past weeks.']);
    }

    public function test_non_admin_can_view_current_or_future_week()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $futureWeek = Week::factory()->create(['week' => Carbon::now()->weekOfYear + 1]);
        $this->actingAs($user, 'sanctum');

        $response = $this->getJson("/api/weeks/{$futureWeek->id}");
        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Week retrieved successfully']);
    }
}
