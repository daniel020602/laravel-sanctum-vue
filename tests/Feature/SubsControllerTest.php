<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Sub;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SubsControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_guest_cannot_create_sub()
    {
        $response = $this->postJson('/api/subs', [
            'name' => 'Test Sub',
            'email' => 'test@example.com',
        ]);
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_create_sub()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');
        $response = $this->postJson('/api/subs', [
            'name' => 'Test Sub',
            'email' => 'test@example.com',
        ]);
        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Test Sub']);
    }

    public function test_admin_can_update_sub()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $sub = Sub::factory()->create();
        $this->actingAs($user, 'sanctum');
        $response = $this->putJson('/api/subs/' . $sub->id, [
            'name' => 'Updated Sub',
            'email' => 'updated@example.com',
        ]);
        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Updated Sub']);
    }

    public function test_non_admin_cannot_update_sub()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $sub = Sub::factory()->create();
        $this->actingAs($user, 'sanctum');
        $response = $this->putJson('/api/subs/' . $sub->id, [
            'name' => 'Should Not Update',
            'email' => 'shouldnot@example.com',
        ]);
        $response->assertStatus(403);
    }

    public function test_admin_can_delete_sub()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $sub = Sub::factory()->create();
        $this->actingAs($user, 'sanctum');
        $response = $this->deleteJson('/api/subs/' . $sub->id);
        $response->assertStatus(200);
        $this->assertDatabaseMissing('subs', ['id' => $sub->id]);
    }

    public function test_non_admin_cannot_delete_sub()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $sub = Sub::factory()->create();
        $this->actingAs($user, 'sanctum');
        $response = $this->deleteJson('/api/subs/' . $sub->id);
        $response->assertStatus(403);
    }

    public function test_authenticated_user_can_view_subs()
    {
        $user = User::factory()->create();
        Sub::factory()->count(2)->create();
        $this->actingAs($user, 'sanctum');
        $response = $this->getJson('/api/subs');
        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Subs retrieved successfully']);
    }
}
