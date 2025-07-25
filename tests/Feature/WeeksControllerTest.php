<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Week;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class WeeksControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_guest_cannot_create_week()
    {
        $response = $this->postJson('/api/weeks', [
            'week' => 30,
            'year' => 2025
        ]);
        $response->assertStatus(401);
    }

    public function test_admin_can_create_week()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user, 'sanctum');
        $response = $this->postJson('/api/weeks', [
            'week' => 30,
            'year' => 2025
        ]);
        $response->assertStatus(201)
            ->assertJsonFragment(['week' => 30]);
    }

    public function test_non_admin_cannot_create_week()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user, 'sanctum');
        $response = $this->postJson('/api/weeks', [
            'week' => 30,
            'year' => 2025
        ]);
        $response->assertStatus(403);
    }

    public function test_admin_can_update_week()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $week = Week::factory()->create(['week' => 30, 'year' => 2025]);
        $this->actingAs($user, 'sanctum');
        $response = $this->putJson('/api/weeks/' . $week->id, [
            'week' => 31,
            'year' => 2025
        ]);
        $response->assertStatus(200)
            ->assertJsonFragment(['week' => 31]);
    }

    public function test_non_admin_cannot_update_week()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $week = Week::factory()->create(['week' => 30, 'year' => 2025]);
        $this->actingAs($user, 'sanctum');
        $response = $this->putJson('/api/weeks/' . $week->id, [
            'week' => 31,
            'year' => 2025
        ]);
        $response->assertStatus(403);
    }

    public function test_admin_can_delete_week()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $week = Week::factory()->create();
        $this->actingAs($user, 'sanctum');
        $response = $this->deleteJson('/api/weeks/' . $week->id);
        $response->assertStatus(200);
        $this->assertDatabaseMissing('weeks', ['id' => $week->id]);
    }

    public function test_non_admin_cannot_delete_week()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $week = Week::factory()->create();
        $this->actingAs($user, 'sanctum');
        $response = $this->deleteJson('/api/weeks/' . $week->id);
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
    }

    public function test_non_admin_can_view_current_and_future_weeks()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $currentWeek = now()->weekOfYear;
        Week::factory()->create(['week' => $currentWeek - 1, 'year' => now()->year]);
        Week::factory()->create(['week' => $currentWeek, 'year' => now()->year]);
        Week::factory()->create(['week' => $currentWeek + 1, 'year' => now()->year]);
        $this->actingAs($user, 'sanctum');
        $response = $this->getJson('/api/weeks');
        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Weeks retrieved successfully']);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_non_admin_cannot_view_past_week()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $pastWeek = Week::factory()->create(['week' => now()->weekOfYear - 1, 'year' => now()->year]);
        $this->actingAs($user, 'sanctum');
        $response = $this->getJson('/api/weeks/' . $pastWeek->id);
        $response->assertStatus(403)
            ->assertJsonFragment(['message' => 'You cannot view past weeks.']);
    }

    public function test_admin_can_view_past_week()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $pastWeek = Week::factory()->create(['week' => now()->weekOfYear - 1, 'year' => now()->year]);
        $this->actingAs($user, 'sanctum');
        $response = $this->getJson('/api/weeks/' . $pastWeek->id);
        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Week retrieved successfully']);
    }
}
