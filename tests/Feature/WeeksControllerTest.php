<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\User;
use App\Models\Week;
use App\Models\WeekMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class WeeksControllerTest extends TestCase
{
	use RefreshDatabase, WithFaker;

	private function examplePayload()
	{
		return [
			'year' => 2025,
			'week_number' => 50,
			'start_date' => '2025-12-08',
			'end_date' => '2025-12-12',
			'menus' => [
				'day1' => ['soup' => 1, 'a' => 2, 'b' => 3, 'c' => 4],
				'day2' => ['soup' => 1, 'a' => 2, 'b' => 3, 'c' => 4],
				'day3' => ['soup' => 1, 'a' => 2, 'b' => 3, 'c' => 4],
				'day4' => ['soup' => 1, 'a' => 2, 'b' => 3, 'c' => 4],
				'day5' => ['soup' => 1, 'a' => 2, 'b' => 3, 'c' => 4],
			]
		];
	}

	public function test_non_admin_index_shows_upcoming_weeks_only()
	{
		// authenticate as a regular user (controller requires auth)
		$user = User::factory()->create(['is_admin' => false]);
		$this->actingAs($user, 'sanctum');

		// create past and future weeks
		Week::factory()->create(['week_number' => now()->weekOfYear - 2]);
		Week::factory()->create(['week_number' => now()->weekOfYear + 1]);

		$response = $this->getJson('/api/weeks');
		$response->assertStatus(200)
			->assertJsonStructure(['weeks']);
		$json = $response->json('weeks');
		foreach ($json as $w) {
			$this->assertTrue($w['week_number'] >= now()->weekOfYear);
		}
	}

	public function test_admin_index_shows_all_weeks()
	{
		$user = User::factory()->create(['is_admin' => true]);
		$this->actingAs($user, 'sanctum');

		Week::factory()->count(3)->create();

		$response = $this->getJson('/api/weeks');
		$response->assertStatus(200)
			->assertJsonStructure(['weeks']);
		$this->assertCount(3, $response->json('weeks'));
	}

	public function test_show_denies_non_admin_for_past_week()
	{
		$user = User::factory()->create(['is_admin' => false]);
		$this->actingAs($user, 'sanctum');

		$week = Week::factory()->create(['week_number' => now()->weekOfYear - 1]);
		$response = $this->getJson('/api/weeks/' . $week->id);
		$response->assertStatus(403)
			->assertJson(['message' => 'Access denied to past weeks.']);
	}

	public function test_show_allows_admin_for_past_week()
	{
		$user = User::factory()->create(['is_admin' => true]);
		$this->actingAs($user, 'sanctum');
		$week = Week::factory()->create(['week_number' => now()->weekOfYear - 1]);
		WeekMenu::factory()->count(4)->create(['week_id' => $week->id]);

		$response = $this->getJson('/api/weeks/' . $week->id);
		$response->assertStatus(200)
			->assertJsonStructure(['week', 'menus'])
			->assertJsonFragment(['id' => $week->id]);
	}

	public function test_non_admin_cannot_store_week()
	{
		$user = User::factory()->create(['is_admin' => false]);
		$this->actingAs($user, 'sanctum');

		$payload = $this->examplePayload();
		// ensure menus reference real menu ids (unique per day) so validation passes
		$menus = Menu::factory()->count(20)->create();
		foreach ($payload['menus'] as $day => $options) {
			$rand = $menus->random(4)->values();
			$i = 0;
			foreach ($options as $opt => $val) {
				$payload['menus'][$day][$opt] = $rand[$i++]->id;
			}
		}

		$response = $this->postJson('/api/weeks', $payload);
		// validation should pass, but controller authorizes admin and denies non-admin
		$response->assertStatus(403);
	}

	public function test_admin_can_store_week_with_menus()
	{
		$user = User::factory()->create(['is_admin' => true]);
		$this->actingAs($user, 'sanctum');

		// create menus and assign consistent ids
		$menus = Menu::factory()->count(20)->create();
		$payload = $this->examplePayload();
		// set all menu references to real ids to avoid FK issues
		foreach ($payload['menus'] as $day => $options) {
			$rand = $menus->random(4)->values();
			$i = 0;
			foreach ($options as $opt => $val) {
				$payload['menus'][$day][$opt] = $rand[$i++]->id;
			}
		}

		$response = $this->postJson('/api/weeks', $payload);
		$response->assertStatus(201)
			->assertJsonStructure(['message', 'data' => ['week', 'week_menus']]);

	$this->assertDatabaseHas('weeks', ['week_number' => 50, 'year' => 2025]);
	}

	public function test_admin_can_update_week_menus()
	{
		$user = User::factory()->create(['is_admin' => true]);
		$this->actingAs($user, 'sanctum');

		$week = Week::factory()->create(['week_number' => 40]);
	$menus = Menu::factory()->count(20)->create();

		$payload = $this->examplePayload();
		foreach ($payload['menus'] as $day => $options) {
			$rand = $menus->random(4)->values();
			$i = 0;
			foreach ($options as $opt => $val) {
				$payload['menus'][$day][$opt] = $rand[$i++]->id;
			}
		}

		$response = $this->putJson('/api/weeks/' . $week->id, $payload);
		$response->assertStatus(200)
			->assertJsonStructure(['message', 'data' => ['week', 'week_menus']]);

		$this->assertDatabaseHas('weeks', ['id' => $week->id]);
	}

	public function test_admin_can_destroy_week_and_related_week_menus()
	{
		$user = User::factory()->create(['is_admin' => true]);
		$this->actingAs($user, 'sanctum');

		$week = Week::factory()->create();
		WeekMenu::factory()->create(['week_id' => $week->id]);

		$response = $this->deleteJson('/api/weeks/' . $week->id);
		$response->assertStatus(200)
			->assertJson(['message' => 'Week and related menus deleted successfully.']);

		$this->assertDatabaseMissing('weeks', ['id' => $week->id]);
		$this->assertDatabaseMissing('week_menus', ['week_id' => $week->id]);
	}

	/*public function test_non_admin_cannot_update_or_delete_week()
	{
		$user = User::factory()->create(['is_admin' => false]);
		$this->actingAs($user, 'sanctum');

		$week = Week::factory()->create();
		$response = $this->putJson('/api/weeks/' . $week->id, $this->examplePayload());
		$response->assertStatus(403);

		$response = $this->deleteJson('/api/weeks/' . $week->id);
		$response->assertStatus(403);
	}*/
}
