<?php

namespace Tests\Feature;

use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use App\Models\Table;
use Illuminate\Support\Facades\Mail;

use function Laravel\Prompts\table;

class ReservationControllerTest extends TestCase
{
    use RefreshDatabase;
    protected $table;
    protected $reservation;

    public function setUp(): void
    {
        parent::setUp();
        Mail::fake(); // Mock the mail sending
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

    public function test_can_list_reservations()
    {
        $response = $this->getJson('/api/reservations');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'reservations' => [
                    ['date', 'time', 'table_id']
                ]
            ]);
    }

    public function test_can_create_reservation()
    {
        $data = Reservation::factory()->make([
            'date' => now()->toDateString(),
            'email' => 'test@example.com',
            'name' => 'Test User',
            'phone' => '123456789',
            'time' => '14:00',
            'table_id' => $this->table->id
        ])->toArray();
        unset($data['reservation_code']); // Will be generated in controller

        $response = $this->postJson('/api/reservations', $data);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'reservation' => ['id', 'date', 'time', 'table_id', 'reservation_code']
            ]);
        $this->assertDatabaseHas('reservations', [
            'date' => $data['date'],
            'time' => $data['time'],
            'table_id' => $data['table_id']
        ]);
    }

    public function test_cannot_create_reservation_with_invalid_data()
    {
        $response = $this->postJson('/api/reservations', []);
        $response->assertStatus(422);
    }

    public function test_can_show_reservation_with_correct_code()
    {
        $response = $this->getJson("/api/reservations/{$this->reservation->id}?reservation_code={$this->reservation->reservation_code}");
        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $this->reservation->id]);
    }

    public function test_cannot_show_reservation_with_wrong_code()
    {
        $response = $this->getJson("/api/reservations/{$this->reservation->id}?reservation_code=wrongcode");
        $response->assertStatus(400)
            ->assertJson(['message' => 'Invalid reservation code']);
    }

    public function test_can_update_reservation_with_correct_code()
    {
        $newData = [
            'time' => '18:00',
        ];
        $response = $this->putJson(
            "/api/reservations/{$this->reservation->id}?reservation_code={$this->reservation->reservation_code}",
            $newData
        );
        $response->assertStatus(200)
            ->assertJsonFragment(['time' => $newData['time']]);
    }

    public function test_cannot_update_reservation_with_wrong_code()
    {
        $newData = [
            'time' => '18:00',
        ];
        $response = $this->putJson(
            "/api/reservations/{$this->reservation->id}?reservation_code=wrongcode",
            $newData
        );
        $response->assertStatus(400)
            ->assertJsonFragment(['message' => 'Invalid reservation code']);
    }

    public function test_can_delete_reservation_with_correct_code()
    {
        $response = $this->deleteJson(
            "/api/reservations/{$this->reservation->id}?reservation_code={$this->reservation->reservation_code}"
        );
        $response->assertStatus(204);
        $this->assertDatabaseMissing('reservations', ['id' => $this->reservation->id]);
    }

    public function test_cannot_delete_reservation_with_wrong_code()
    {
        $response = $this->deleteJson(
            "/api/reservations/{$this->reservation->id}?reservation_code=wrongcode"
        );
        $response->assertStatus(400)
            ->assertJson(['message' => 'Invalid reservation code']);
    }

    public function test_can_confirm_reservation_with_correct_code()
    {
        $response = $this->postJson(
            "/api/reservations/{$this->reservation->id}/confirm",
            ['reservation_code' => $this->reservation->reservation_code]
        );
        $response->assertStatus(200)
            ->assertJsonFragment(['is_confirmed' => true]);
        $this->assertDatabaseHas('reservations', [
            'id' => $this->reservation->id,
            'is_confirmed' => true
        ]);
    }

    public function test_cannot_confirm_reservation_with_wrong_code()
    {
        $response = $this->postJson(
            "/api/reservations/{$this->reservation->id}/confirm",
            ['reservation_code' => 'fasz']
        );
        $response->assertStatus(400)
            ->assertJson(['message' => 'Invalid reservation code or reservation already confirmed']);
    }

    public function test_confirm_returns_already_confirmed_when_already_confirmed()
    {
        // mark reservation as already confirmed
        $this->reservation->is_confirmed = true;
        $this->reservation->save();

        $response = $this->postJson(
            "/api/reservations/{$this->reservation->id}/confirm",
            ['reservation_code' => $this->reservation->reservation_code]
        );

        $response->assertStatus(200)
            ->assertJson(['message' => 'Reservation already confirmed']);
    }

    private function makeReservationData(array $overrides = [])
    {
        return array_merge([
            'date' => now()->toDateString(),
            'email' => 'test@example.com',
            'name' => 'Test User',
            'phone' => '1234567890',
            'time' => '12:00',
            'table_id' => $this->table->id
        ], $overrides);
    }
}
