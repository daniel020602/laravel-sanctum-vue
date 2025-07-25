<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class MenuControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_guest_can_view_menu_index()
    {
        $menus = Menu::factory()->count(3)->create();
        $response = $this->getJson('/api/menus');
        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    public function test_guest_can_view_single_menu()
    {
        $menu = Menu::factory()->create();
        $response = $this->getJson('/api/menus/' . $menu->id);
        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $menu->id]);
    }

    public function test_non_admin_cannot_create_menu()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user, 'sanctum');
        $response = $this->postJson('/api/menus', [
            'name' => 'Test Menu',
            'type' => 'main',
            'price' => 10.50
        ]);
        $response->assertStatus(403);
    }

    public function test_admin_can_create_menu()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user, 'sanctum');
        $response = $this->postJson('/api/menus', [
            'name' => 'Test Menu',
            'type' => 'main',
            'price' => 10.50
        ]);
        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Test Menu']);
    }

    public function test_admin_can_update_menu()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $menu = Menu::factory()->create();
        $this->actingAs($user, 'sanctum');
        $response = $this->putJson('/api/menus/' . $menu->id, [
            'name' => 'Updated Menu',
            'type' => 'main',
            'price' => 15.00
        ]);
        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Updated Menu']);
    }

    public function test_admin_can_delete_menu()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $menu = Menu::factory()->create();
        $this->actingAs($user, 'sanctum');
        $response = $this->deleteJson('/api/menus/' . $menu->id);
        $response->assertStatus(200);
        $this->assertDatabaseMissing('menus', ['id' => $menu->id]);
    }

    public function test_non_admin_cannot_update_or_delete_menu()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $menu = Menu::factory()->create();
        $this->actingAs($user, 'sanctum');
        $response = $this->putJson('/api/menus/' . $menu->id, [
            'name' => 'Should Not Update',
            'type' => 'main',
            'price' => 20.00
        ]);
        $response->assertStatus(403);
        $response = $this->deleteJson('/api/menus/' . $menu->id);
        $response->assertStatus(403);
    }
}
