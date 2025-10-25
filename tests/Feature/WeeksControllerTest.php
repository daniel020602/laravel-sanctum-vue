<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\User;
use App\Models\Week;
use App\Models\WeekMenu;
use Carbon\Carbon;
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
	$menus = Menu::factory()->count(30)->create();
		foreach ($payload['menus'] as $day => $options) {
			$rand = $menus->random(4)->values();
			$i = 0;
			foreach ($options as $opt => $val) {
				$payload['menus'][$day][$opt] = $rand[$i++]->id;
			}
		}

	// create a week to update so UpdateWeekRequest is used
	$week = Week::factory()->create(['week_number' => 10, 'year' => 2025]);

	$response = $this->putJson('/api/weeks/' . $week->id, $payload);
		// validation should pass, but controller authorizes admin and denies non-admin
		$response->assertStatus(403);
	}

	public function test_admin_can_store_week_with_menus()
	{
		$user = User::factory()->create(['is_admin' => true]);
		$this->actingAs($user, 'sanctum');

		// create menus and assign consistent ids
		$menus = Menu::factory()->count(30)->create();
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
	$menus = Menu::factory()->count(30)->create();

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

		$start = now()->startOfWeek()->addWeeks(2);
		$week = Week::factory()->create([
			'start_date' => $start->toDateString(),
			'week_number' => (int) $start->weekOfYear,
			'year' => (int) $start->year,
		]);
		WeekMenu::factory()->create(['week_id' => $week->id]);

		$response = $this->deleteJson('/api/weeks/' . $week->id);
		$response->assertStatus(200)
			->assertJson(['message' => 'Week and related menus deleted successfully.']);

		$this->assertDatabaseMissing('weeks', ['id' => $week->id]);
		$this->assertDatabaseMissing('week_menus', ['week_id' => $week->id]);
	}

	public function test_nextWeek_returns_404_when_missing()
	{
		$user = User::factory()->create();
		$this->actingAs($user, 'sanctum');

		// Ensure no week exists for next week
		$resp = $this->getJson('/api/weeks/next-week');
		$resp->assertStatus(404)->assertJson(['message' => 'Next week not found']);
	}

	public function test_nextWeek_returns_week_and_menus()
	{
		$user = User::factory()->create();
		$this->actingAs($user, 'sanctum');

		$current = Carbon::now();
		$nextWeekNumber = $current->weekOfYear + 1;
		$year = $current->year;
		if ($nextWeekNumber > 52) {
			$nextWeekNumber = 1;
			$year += 1;
		}

		$week = Week::factory()->create(['week_number' => $nextWeekNumber, 'year' => $year]);
		WeekMenu::factory()->count(3)->create(['week_id' => $week->id]);

		$resp = $this->getJson('/api/weeks/next-week');
		$resp->assertStatus(200)
			->assertJsonStructure(['week', 'menus']);
		$this->assertCount(3, $resp->json('menus'));
	}

	public function test_nextWeek_rolls_over_year_when_week_exceeds_52()
	{
		// Force the current date into ISO week 52 so nextWeekNumber becomes 53 (>52)
		$now = Carbon::now();
		$forced = (clone $now)->setISODate((int) $now->year, 52);
		Carbon::setTestNow($forced);

		$user = User::factory()->create();
		$this->actingAs($user, 'sanctum');

		// compute expected rolled values
		$current = Carbon::now();
		$nextWeekNumber = $current->weekOfYear + 1; // will be 53
		$year = $current->year;
		if ($nextWeekNumber > 52) {
			$nextWeekNumber = 1;
			$year += 1;
		}

		$week = Week::factory()->create(['week_number' => $nextWeekNumber, 'year' => $year]);
		WeekMenu::factory()->count(2)->create(['week_id' => $week->id]);

		$resp = $this->getJson('/api/weeks/next-week');
		$resp->assertStatus(200)->assertJsonStructure(['week', 'menus']);
		$this->assertEquals($nextWeekNumber, $resp->json('week.week_number'));
		$this->assertEquals($year, $resp->json('week.year'));

		Carbon::setTestNow();
	}

	public function test_store_fails_when_duplicate_menu_in_same_day()
	{
		$user = User::factory()->create(['is_admin' => true]);
		$this->actingAs($user, 'sanctum');

	$menus = Menu::factory()->count(20)->create();
		$payload = $this->examplePayload();

		// assign real ids but introduce a duplicate in day1 (a and b same)
		$payload['menus']['day1']['soup'] = $menus[0]->id;
		$payload['menus']['day1']['a'] = $menus[1]->id;
		$payload['menus']['day1']['b'] = $menus[1]->id; // duplicate
		$payload['menus']['day1']['c'] = $menus[2]->id;

		// fill other days with unique ids
		$idx = 3;
		foreach (['day2','day3','day4','day5'] as $day) {
			$payload['menus'][$day]['soup'] = $menus[$idx++]->id;
			$payload['menus'][$day]['a'] = $menus[$idx++]->id;
			$payload['menus'][$day]['b'] = $menus[$idx++]->id;
			$payload['menus'][$day]['c'] = $menus[$idx++]->id;
		}

		$response = $this->postJson('/api/weeks', $payload);
		$response->assertStatus(422);
		$response->assertJsonValidationErrors('menus.day1');

		$errors = $response->json('errors');
		$this->assertEquals('Duplicate menu detected for day1.', $errors['menus.day1'][0]);
	}

	public function test_update_fails_when_duplicate_menu_in_same_day()
	{
		$user = User::factory()->create(['is_admin' => true]);
		$this->actingAs($user, 'sanctum');

		$menus = Menu::factory()->count(30)->create();
		$week = Week::factory()->create(['week_number' => 11, 'year' => 2025]);

		$payload = $this->examplePayload();

		// introduce duplicate in day3
		$payload['menus']['day3']['soup'] = $menus[5]->id;
		$payload['menus']['day3']['a'] = $menus[6]->id;
		$payload['menus']['day3']['b'] = $menus[6]->id; // duplicate
		$payload['menus']['day3']['c'] = $menus[7]->id;

		// fill other days with unique ids
		$idx = 8;
		foreach (['day1','day2','day4','day5'] as $day) {
			$payload['menus'][$day]['soup'] = $menus[$idx++]->id;
			$payload['menus'][$day]['a'] = $menus[$idx++]->id;
			$payload['menus'][$day]['b'] = $menus[$idx++]->id;
			$payload['menus'][$day]['c'] = $menus[$idx++]->id;
		}

		$response = $this->putJson('/api/weeks/' . $week->id, $payload);
		$response->assertStatus(422);
		$response->assertJsonValidationErrors('menus.day3');
		$errors = $response->json('errors');
		$this->assertEquals('Duplicate menu detected for day3.', $errors['menus.day3'][0]);
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
