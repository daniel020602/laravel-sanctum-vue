<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Str;

class UpdateResRequestTest extends TestCase
{
    use RefreshDatabase;
    protected $table;
    protected $reservation;
    protected function setUp(): void
    {
        parent::setUp();
        // Create a table for reservation tests
        $this->table = Table::factory()->create(['capacity' => 4]); 
        $this->reservation = Reservation::factory()->create([
            'date' => now()->toDateString(),
            'email' => 'test@example.com',
            'name' => 'Test User',
            'phone' => '1234567890',
            'time' => '12:00',
            'table_id' => $this->table->id,
            'reservation_code' => "testcode12",
            
        ]);
    }

    /** @test */
    public function it_allows_valid_update_data()
    {
        $reservation = $this->reservation;

        $payload = [
            'phone' => '0987654321',
            'date' => '2025-08-22',
            'time' => '19:00',
            'table_id' => $this->table->id,
            'email' => 'test@example.com',
            'reservation_code' => 'testcode12',
            'name' => 'John Doe',
        ];

        $response = $this->putJson("/api/reservations/{$reservation->id}", $payload);
        $response->assertStatus(200);
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'phone' => '0987654321',
            'date' => '2025-08-22',
            'time' => '19:00',
        ]);
    }

    /** @test */
    public function it_rejects_duplicate_phone()
    {
        $reservation1 = $this->reservation;
        $reservation2 = Reservation::factory()->create([
            'date' => now()->toDateString(),
            'email' => 'test@example.com',
            'name' => 'Test User',
            'phone' => '2222222222',
            'time' => '14:00',
            'table_id' => $this->table->id,
            'reservation_code' => "testcode13",
            
        ]);

        $response = $this->putJson("/api/reservations/{$reservation2->id}", [
            'phone' => '1234567890',
            'reservation_code' => 'testcode13'
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('phone');
    }

    /** @test */
    public function it_rejects_invalid_date_format()
    {
        $reservation = $this->reservation;

        $response = $this->putJson("/api/reservations/{$reservation->id}", [
            'date' => 'not-a-date',
            'reservation_code' => 'testcode12'
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('date');
    }

    /** @test */
    public function it_rejects_invalid_time_format()
    {
        $reservation = $this->reservation;

        $response = $this->putJson("/api/reservations/{$reservation->id}", [
            'time' => '25:99',
            'reservation_code' => 'testcode12'
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('time');
    }

    /** @test */
    public function it_rejects_duplicate_table_reservation_for_same_date_and_time()
    {
        $table = $this->table;
        $reservation = $this->reservation;
        $this->postJson("/api/reservations", [
            'date' => now()->toDateString(),
            'email' => 'test@example.com',
            'name' => 'Test User',
            'phone' => '2222222222',
            'time' => '14:00',
            'table_id' => $this->table->id,
            'reservation_code' => 'testcode13',
        ]);

        $response = $this->putJson("/api/reservations/{$reservation->id}", [
            'table_id' => $table->id,
            'date' => now()->toDateString(),
            'time' => '14:00',
            'reservation_code' => 'testcode12'
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('table_id');
    }
}
