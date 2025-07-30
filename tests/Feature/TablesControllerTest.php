<?php

namespace Tests\Feature;

use App\Models\Table;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TablesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_tables_index()
    {
        Table::factory()->count(3)->create();
        $response = $this->getJson('/api/tables');
        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    public function test_guest_can_view_single_table()
    {
        $table = Table::factory()->create();
        $response = $this->getJson('/api/tables/' . $table->id);
        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $table->id]);
    }

    public function test_non_admin_cannot_create_table()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user, 'sanctum');
        $response = $this->postJson('/api/tables', [
            'capacity' => 4
        ]);
        $response->assertStatus(403);
    }

    public function test_admin_can_create_table()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user, 'sanctum');
        $response = $this->postJson('/api/tables', [
            'capacity' => 6
        ]);
        $response->assertStatus(201)
            ->assertJsonFragment(['capacity' => 6]);
    }

    public function test_admin_can_update_table()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $table = Table::factory()->create(['capacity' => 4]);
        $this->actingAs($user, 'sanctum');
        $response = $this->putJson('/api/tables/' . $table->id, [
            'capacity' => 8
        ]);
        $response->assertStatus(200)
            ->assertJsonFragment(['capacity' => 8]);
    }

    public function test_non_admin_cannot_update_table()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $table = Table::factory()->create(['capacity' => 4]);
        $this->actingAs($user, 'sanctum');
        $response = $this->putJson('/api/tables/' . $table->id, [
            'capacity' => 10
        ]);
        $response->assertStatus(403);
    }

    public function test_admin_can_delete_table()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $table = Table::factory()->create();
        $this->actingAs($user, 'sanctum');
        $response = $this->deleteJson('/api/tables/' . $table->id);
        $response->assertStatus(200);
        $this->assertDatabaseMissing('tables', ['id' => $table->id]);
    }

    public function test_non_admin_cannot_delete_table()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $table = Table::factory()->create();
        $this->actingAs($user, 'sanctum');
        $response = $this->deleteJson('/api/tables/' . $table->id);
        $response->assertStatus(403);
    }
}
