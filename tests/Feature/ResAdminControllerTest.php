<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\User;
use App\Models\OldReservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use App\Models\Table;

class ResAdminControllerTest extends TestCase
{
    use RefreshDatabase;
    protected $admin;
    protected $reservation;
    protected $table;

    public function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->table = Table::factory()->create(['capacity' => 4]);
        $this->reservation = Reservation::factory()->create([
            'date' => now()->toDateString(),
            'email' => 'test@example.com',
            'name' => 'Test User',
            'phone' => '1234567890',
            'time' => '12:00',
            'table_id' => $this->table->id,
            'reservation_code' => Str::random(10),
        ]);
    }

    public function test_admin_can_list_reservations()
    {
        $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/res-admin');
        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $this->reservation->id]);
    }

    public function test_non_admin_cannot_list_reservations()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/res-admin');
        $response->assertStatus(403);
    }

    public function test_admin_can_create_reservation()     
    {
        $table = Table::factory()->create(['capacity' => 4]);
         // Create a new reservation with valid data
        $data = [
            'date' => now()->addDay()->toDateString(),
            'email' => 'new@example.com',
            'name' => 'New User',
            'phone' => '9876543210',
            'time' => '14:00',
            'table_id' => $table->id,
            'reservation_code' => Str::random(10),
        ];
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/res-admin', $data);
        $response->assertStatus(201)
            ->assertJsonFragment(['email' => 'new@example.com']);
        $this->assertDatabaseHas('reservations', ['email' => 'new@example.com']);
    }

    public function test_admin_can_show_reservation()
    {
        $response = $this->actingAs($this->admin, 'sanctum')->getJson("/api/res-admin/{$this->reservation->id}");
        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $this->reservation->id]);
    }

    public function test_admin_can_update_reservation()
    {
        $newData = [
            'name' => 'Updated Name',
            'reservation_code' => $this->reservation->reservation_code, 
        ];
        $response = $this->actingAs($this->admin, 'sanctum')->putJson("/api/res-admin/{$this->reservation->id}", $newData);
        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Updated Name']);
        $this->assertDatabaseHas('reservations', ['id' => $this->reservation->id, 'name' => 'Updated Name']);
    }

    public function test_admin_can_delete_reservation()
    {
        $response = $this->actingAs($this->admin, 'sanctum')->deleteJson("/api/res-admin/{$this->reservation->id}");
        $response->assertStatus(204);
        $this->assertDatabaseMissing('reservations', ['id' => $this->reservation->id]);
    }

    public function test_admin_can_complete_reservation()
    {
        $response = $this->actingAs($this->admin, 'sanctum')->postJson("/api/res-admin/{$this->reservation->id}/complete");
        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Reservation completed successfully']);
        $this->assertDatabaseMissing('reservations', ['id' => $this->reservation->id]);
        $this->assertDatabaseHas('old_reservations', [
            'name' => $this->reservation->name,
            'email' => $this->reservation->email,
            'phone' => $this->reservation->phone,
            'date' => $this->reservation->date,
            'time' => $this->reservation->time,
            'table_id' => $this->reservation->table_id,
        ]);
    }

    /** @test */
    public function count_unconfirmed_returns_only_old_unconfirmed_reservations()
    {
        // create tables used by the test so foreign keys are valid
        $t1 = Table::factory()->create();
        $t2 = Table::factory()->create();
        $t3 = Table::factory()->create();

        // old unconfirmed (should be counted)
        $r1 = Reservation::create([
            'reservation_code' => 'ABC1',
            'name' => 'Old Unconfirmed',
            'email' => 'old@example.com',
            'phone' => '123',
            'date' => now()->toDateString(),
            'time' => now()->toTimeString(),
            'table_id' => $t1->id,
            'is_confirmed' => false,
        ]);
        $r1->created_at = now()->subDays(2);
        $r1->save();

        // today unconfirmed (should NOT be counted)
        $r2 = Reservation::create([
            'reservation_code' => 'ABC2',
            'name' => 'Today Unconfirmed',
            'email' => 'today@example.com',
            'phone' => '456',
            'date' => now()->toDateString(),
            'time' => now()->toTimeString(),
            'table_id' => $t2->id,
            'is_confirmed' => false,
        ]);

        // old but confirmed (should NOT be counted)
        $r3 = Reservation::create([
            'reservation_code' => 'ABC3',
            'name' => 'Old Confirmed',
            'email' => 'oldconf@example.com',
            'phone' => '789',
            'date' => now()->toDateString(),
            'time' => now()->toTimeString(),
            'table_id' => $t3->id,
            'is_confirmed' => true,
        ]);
        $r3->created_at = now()->subDays(3);
        $r3->save();

        $res = $this->actingAs($this->admin, 'sanctum')->getJson('/api/res-admin/unconfirmed-count');
        $res->assertStatus(200)->assertJson(['unconfirmed_count' => 1]);
    }

    /** @test */
    public function delete_unconfirmed_reservations_removes_only_old_unconfirmed()
    {
        // create tables used by the test so foreign keys are valid
        $t1 = Table::factory()->create();
        $t2 = Table::factory()->create();
        $t3 = Table::factory()->create();

        // old unconfirmed (should be deleted)
        $r1 = Reservation::create([
            'reservation_code' => 'DEL1',
            'name' => 'To Delete',
            'email' => 'del@example.com',
            'phone' => '111',
            'date' => now()->toDateString(),
            'time' => now()->toTimeString(),
            'table_id' => $t1->id,
            'is_confirmed' => false,
        ]);
        $r1->created_at = now()->subDays(2);
        $r1->save();

        // recent unconfirmed (should remain)
        $r2 = Reservation::create([
            'reservation_code' => 'DEL2',
            'name' => 'Keep',
            'email' => 'keep@example.com',
            'phone' => '222',
            'date' => now()->toDateString(),
            'time' => now()->toTimeString(),
            'table_id' => $t2->id,
            'is_confirmed' => false,
        ]);

        // old confirmed (should remain)
        $r3 = Reservation::create([
            'reservation_code' => 'DEL3',
            'name' => 'Also Keep',
            'email' => 'also@example.com',
            'phone' => '333',
            'date' => now()->toDateString(),
            'time' => now()->toTimeString(),
            'table_id' => $t3->id,
            'is_confirmed' => true,
        ]);
        $r3->created_at = now()->subDays(3);
        $r3->save();

        $res = $this->actingAs($this->admin, 'sanctum')->deleteJson('/api/res-admin/delete-unconfirmed-reservations');
        $res->assertStatus(200)->assertJson(['message' => 'Unconfirmed reservations deleted successfully']);

        $this->assertDatabaseMissing('reservations', ['id' => $r1->id]);
        $this->assertDatabaseHas('reservations', ['id' => $r2->id]);
        $this->assertDatabaseHas('reservations', ['id' => $r3->id]);
    }
}
