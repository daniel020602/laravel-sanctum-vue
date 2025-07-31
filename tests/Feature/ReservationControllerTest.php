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

    public function setUp(): void
    {
        parent::setUp();
        Mail::fake(); // Mock the mail sending
        $this->table = Table::factory()->create(['capacity' => 4]);
    }

    public function test_can_list_reservations()
    {
        Reservation::factory()->create(['date' => now()->toDateString(),'email' => 'test@example.com','name' => 'Test User','phone' => '1234567890','reservation_code' => Str::random(10), 'time' => '12:00', 'table_id' => $this->table->id]);
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
            'phone' => '1234567890',
            'time' => '12:00',
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
        // Step 1: Create reservation via API
        $data = Reservation::factory()->make([
            'date' => now()->toDateString(),
            'email' => 'test@example.com',
            'name' => 'Test User',
            'phone' => '1234567890',
            'time' => '12:00',
            'table_id' => $this->table->id
        ])->toArray();
        unset($data['reservation_code']); // Let controller generate it

        $createResponse = $this->postJson('/api/reservations', $data);
        $createResponse->assertStatus(201);
        $reservation = $createResponse->json('reservation');

        // Step 2: Use the returned code to access the reservation
        $response = $this->getJson("/api/reservations/{$reservation['id']}?reservation_code={$reservation['reservation_code']}");
        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $reservation['id']]);
    }

    public function test_cannot_show_reservation_with_wrong_code()
    {
        // Step 1: Create reservation via API
        $data = Reservation::factory()->make([
            'date' => now()->toDateString(),
            'email' => 'test@example.com',
            'name' => 'Test User',
            'phone' => '1234567890',
            'time' => '12:00',
            'table_id' => $this->table->id
        ])->toArray();
        unset($data['reservation_code']); // Let controller generate it

        $createResponse = $this->postJson('/api/reservations', $data);
        $createResponse->assertStatus(201);
        $reservation = $createResponse->json('reservation');

        $response = $this->getJson("/api/reservations/{$reservation['id']}?reservation_code=wrongcode");
        $response->assertStatus(400)
            ->assertJson(['message' => 'Invalid reservation code']);
    }

    public function test_can_update_reservation_with_correct_code()
    {
        $reservation = Reservation::factory()->create([
            'date' => now()->toDateString(),
            'email' => 'test@example.com',
            'name' => 'Test User',
            'phone' => '1234567890',
            'time' => '12:00',
            'table_id' => $this->table->id,
            'reservation_code' => Str::random(10),
            
        ]);

        $newData = [
            'date' => now()->addDay()->toDateString(),
            'time' => '18:00',
            'table_id' => $reservation->table_id,
            // add other required fields if needed
        ];
        $response = $this->putJson(
            "/api/reservations/{$reservation->id}?reservation_code={$reservation->reservation_code}",
            $newData
        );
        $response->assertStatus(200)
            ->assertJsonFragment(['date' => $newData['date']]);
    }

    public function test_cannot_update_reservation_with_wrong_code()
    {
        $reservation = Reservation::factory()->create();
        $response = $this->putJson(
            "/api/reservations/{$reservation->id}?reservation_code=wrongcode",
            ['date' => now()->addDay()->toDateString()]
        );
        $response->assertStatus(400)
            ->assertJson(['message' => 'Invalid reservation code']);
    }

    public function test_can_delete_reservation_with_correct_code()
    {
        $reservation = Reservation::factory()->create();
        $response = $this->deleteJson(
            "/api/reservations/{$reservation->id}?reservation_code={$reservation->reservation_code}"
        );
        $response->assertStatus(204);
        $this->assertDatabaseMissing('reservations', ['id' => $reservation->id]);
    }

    public function test_cannot_delete_reservation_with_wrong_code()
    {
        $reservation = Reservation::factory()->create();
        $response = $this->deleteJson(
            "/api/reservations/{$reservation->id}?reservation_code=wrongcode"
        );
        $response->assertStatus(400)
            ->assertJson(['message' => 'Invalid reservation code']);
    }

    public function test_can_confirm_reservation_with_correct_code()
    {
        $reservation = Reservation::factory()->create(['is_confirmed' => false]);
        $response = $this->postJson(
            "/api/reservations/{$reservation->id}/confirm",
            ['reservation_code' => $reservation->reservation_code]
        );
        $response->assertStatus(200)
            ->assertJsonFragment(['is_confirmed' => true]);
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'is_confirmed' => true
        ]);
    }

    public function test_cannot_confirm_reservation_with_wrong_code()
    {
        $reservation = Reservation::factory()->create(['is_confirmed' => false]);
        $response = $this->postJson(
            "/api/reservations/{$reservation->id}/confirm",
            ['reservation_code' => 'wrongcode']
        );
        $response->assertStatus(400)
            ->assertJson(['message' => 'Invalid reservation code or reservation already confirmed']);
    }

    public function test_cannot_confirm_already_confirmed_reservation()
    {
        $reservation = Reservation::factory()->create(['is_confirmed' => true]);
        $response = $this->postJson(
            "/api/reservations/{$reservation->id}/confirm",
            ['reservation_code' => $reservation->reservation_code]
        );
        $response->assertStatus(400)
            ->assertJson(['message' => 'Invalid reservation code or reservation already confirmed']);
    }
}
