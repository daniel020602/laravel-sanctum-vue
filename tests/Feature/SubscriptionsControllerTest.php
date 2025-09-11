<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Week;
use App\Models\WeekMenu;
use App\Models\Subscription;
use App\Models\SubscriptionChoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SubscriptionsControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_non_admin_sees_only_their_subscriptions()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $other = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        Subscription::factory()->create(['user_id' => $user->id]);
        Subscription::factory()->create(['user_id' => $other->id]);

        $response = $this->getJson('/api/subscriptions');
        $response->assertStatus(200)
            ->assertJsonStructure(['subscriptions']);
        $subs = $response->json('subscriptions');
        foreach ($subs as $s) {
            $this->assertEquals($user->id, $s['user_id']);
        }
    }

    public function test_admin_sees_all_subscriptions()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user, 'sanctum');

        Subscription::factory()->count(3)->create();
        $response = $this->getJson('/api/subscriptions');
        $response->assertStatus(200)
            ->assertJsonStructure(['subscriptions']);
        $this->assertCount(3, $response->json('subscriptions'));
    }

    public function test_show_allows_owner_or_admin()
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);

        $sub = Subscription::factory()->create(['user_id' => $owner->id]);
        SubscriptionChoice::factory()->count(2)->create(['subscription_id' => $sub->id]);

        // owner
        $this->actingAs($owner, 'sanctum');
        $response = $this->getJson('/api/subscriptions/' . $sub->id);
        $response->assertStatus(200)
            ->assertJsonStructure(['subscription', 'choices']);

        // other should be forbidden
        $this->actingAs($other, 'sanctum');
        $this->getJson('/api/subscriptions/' . $sub->id)->assertStatus(403);

        // admin allowed
        $this->actingAs($admin, 'sanctum');
        $this->getJson('/api/subscriptions/' . $sub->id)->assertStatus(200);
    }

    public function test_store_creates_subscription_and_choices()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $start = now()->addWeek();
        $week = Week::factory()->create([
            'start_date' => $start->toDateString(),
            'week_number' => (int) $start->weekOfYear,
            'year' => (int) $start->year,
        ]);
        // create week menus (option 'a') for each day
        for ($i = 1; $i <= 5; $i++) {
            WeekMenu::factory()->create(['week_id' => $week->id, 'day_of_week' => $i, 'option' => 'a']);
        }

        $response = $this->postJson('/api/subscriptions', ['week_id' => $week->id]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('subscriptions', ['week_id' => $week->id, 'user_id' => $user->id]);
        $sub = Subscription::first();
        $this->assertDatabaseCount('subscription_choices', 5);
    }

    public function test_update_allows_owner_and_validates_week_menus()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

    $start = now()->startOfWeek()->addWeek();
        $week = Week::factory()->create([
            'start_date' => $start->toDateString(),
            'week_number' => (int) $start->weekOfYear,
            'year' => (int) $start->year,
        ]);
        $sub = Subscription::factory()->create(['user_id' => $user->id, 'week_id' => $week->id]);

        // create week menus for week and grab ids keyed by day
        $menusByDay = [];
        foreach (range(1,5) as $day) {
            $wm = WeekMenu::factory()->create(['week_id' => $week->id, 'day_of_week' => $day, 'option' => 'a']);
            $menusByDay[$day] = $wm;
        }

        $choices = [];
        foreach ($menusByDay as $day => $wm) {
            $choices[] = ['week_menu_id' => $wm->id, 'day' => $day];
        }

        $payload = ['week_id' => $week->id, 'choices' => $choices];
        $response = $this->putJson('/api/subscriptions/' . $sub->id, $payload);
        $response->assertStatus(200)->assertJson(['message' => 'Subscription updated']);
    }

    public function test_destroy_only_owner_or_admin_and_future_week()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

    $start = now()->startOfWeek()->addWeek();
        $week = Week::factory()->create([
            'start_date' => $start->toDateString(),
            'week_number' => (int) $start->weekOfYear,
            'year' => (int) $start->year,
        ]);
        $sub = Subscription::factory()->create(['user_id' => $user->id, 'week_id' => $week->id]);

        $response = $this->deleteJson('/api/subscriptions/' . $sub->id);
        $response->assertStatus(200)->assertJson(['message' => 'Subscription deleted']);
        $this->assertDatabaseMissing('subscriptions', ['id' => $sub->id]);
    }

    public function test_store_rejects_past_week_with_400()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        // week in the past
    $week = Week::factory()->create(['start_date' => now()->subDays(10)->toDateString()]);

        $response = $this->postJson('/api/subscriptions', ['week_id' => $week->id]);
        $response->assertStatus(400)
            ->assertJson(['message' => 'Can only subscribe to next week']);
    }

    public function test_update_rejects_past_week_choices()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

    $week = Week::factory()->create(['start_date' => now()->subDays(5)->toDateString()]);
        $sub = Subscription::factory()->create(['user_id' => $user->id, 'week_id' => $week->id]);

        $response = $this->putJson('/api/subscriptions/' . $sub->id, ['week_id' => $week->id, 'choices' => []]);
        $response->assertStatus(400)
            ->assertJson(['message' => 'Can only change future week choices']);
    }

    public function test_update_rejects_subscription_week_mismatch()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

    $startA = now()->startOfWeek()->addWeek();
    $weekA = Week::factory()->create([
            'start_date' => $startA->toDateString(),
            'week_number' => (int) $startA->weekOfYear,
            'year' => (int) $startA->year,
        ]);
    $startB = now()->startOfWeek()->addWeeks(2);
        $weekB = Week::factory()->create([
            'start_date' => $startB->toDateString(),
            'week_number' => (int) $startB->weekOfYear,
            'year' => (int) $startB->year,
        ]);

        $sub = Subscription::factory()->create(['user_id' => $user->id, 'week_id' => $weekA->id]);

        // create a valid week_menu for weekB to reference in payload
        $wm = WeekMenu::factory()->create(['week_id' => $weekB->id, 'day_of_week' => 1, 'option' => 'a']);

        $payload = ['week_id' => $weekB->id, 'choices' => [['week_menu_id' => $wm->id, 'day' => 1]]];
        $response = $this->putJson('/api/subscriptions/' . $sub->id, $payload);
        $response->assertStatus(400)
            ->assertJson(['message' => 'Subscription week does not match provided week_id']);
    }

    public function test_update_rejects_week_menu_not_belonging_to_requested_week()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

    $start = now()->startOfWeek()->addWeek();
        $week = Week::factory()->create([
            'start_date' => $start->toDateString(),
            'week_number' => (int) $start->weekOfYear,
            'year' => (int) $start->year,
        ]);
    $startOther = now()->startOfWeek()->addWeeks(2);
        $otherWeek = Week::factory()->create([
            'start_date' => $startOther->toDateString(),
            'week_number' => (int) $startOther->weekOfYear,
            'year' => (int) $startOther->year,
        ]);

        $sub = Subscription::factory()->create(['user_id' => $user->id, 'week_id' => $week->id]);

        // create a week_menu that belongs to otherWeek
        $wmOther = WeekMenu::factory()->create(['week_id' => $otherWeek->id, 'day_of_week' => 1, 'option' => 'a']);

        // Attempt to update sub for $week but include a week_menu from otherWeek
        $payload = ['week_id' => $week->id, 'choices' => [['week_menu_id' => $wmOther->id, 'day' => 1]]];
        $response = $this->putJson('/api/subscriptions/' . $sub->id, $payload);
        $response->assertStatus(400)
            ->assertJsonFragment(['message' => "Week menu {$wmOther->id} does not belong to week {$week->id}"]);
    }

    public function test_update_rejects_choice_day_mismatch()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

    $start = now()->startOfWeek()->addWeek();
        $week = Week::factory()->create([
            'start_date' => $start->toDateString(),
            'week_number' => (int) $start->weekOfYear,
            'year' => (int) $start->year,
        ]);
        $sub = Subscription::factory()->create(['user_id' => $user->id, 'week_id' => $week->id]);

        // Create a week menu that belongs to the same week but has day_of_week = 2
        $wm = WeekMenu::factory()->create(['week_id' => $week->id, 'day_of_week' => 2, 'option' => 'a']);

        // Provide a choice with day = 1 which does not match wm.day_of_week
        $payload = ['week_id' => $week->id, 'choices' => [['week_menu_id' => $wm->id, 'day' => 1]]];
        $response = $this->putJson('/api/subscriptions/' . $sub->id, $payload);
        $response->assertStatus(400)
            ->assertJson(['message' => 'Day does not match the week menu day']);
    }

    public function test_destroy_rejects_past_week_with_400()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

    $week = Week::factory()->create(['start_date' => now()->subDays(3)->toDateString()]);
        $sub = Subscription::factory()->create(['user_id' => $user->id, 'week_id' => $week->id]);

        $response = $this->deleteJson('/api/subscriptions/' . $sub->id);
        $response->assertStatus(400)
            ->assertJson(['message' => 'Can only change future week choices']);
    }

    public function test_store_returns_402_on_payment_failure()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

    $start = now()->startOfWeek()->addWeek();
        $week = Week::factory()->create([
            'start_date' => $start->toDateString(),
            'week_number' => (int) $start->weekOfYear,
            'year' => (int) $start->year,
        ]);

        // force mock payment to fail
        $response = $this->postJson('/api/subscriptions', ['week_id' => $week->id, 'mock_charge_status' => 'failed']);
        $response->assertStatus(402)
            ->assertJson(['message' => 'Payment failed']);
    }

    public function test_show_returns_404_for_missing_subscription()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $this->getJson('/api/subscriptions/999999')->assertStatus(404);
    }

    public function test_destroy_returns_404_for_missing_subscription()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $this->deleteJson('/api/subscriptions/999999')->assertStatus(404);
    }

    public function test_update_forbidden_for_non_owner()
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($other, 'sanctum');

        $start = now()->addDay();
        $week = Week::factory()->create([
            'start_date' => $start->toDateString(),
            'week_number' => (int) $start->weekOfYear,
            'year' => (int) $start->year,
        ]);
        $sub = Subscription::factory()->create(['user_id' => $owner->id, 'week_id' => $week->id]);

        $response = $this->putJson('/api/subscriptions/' . $sub->id, ['week_id' => $week->id, 'choices' => []]);
        $response->assertStatus(403);
    }

    public function test_destroy_forbidden_for_non_owner()
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($other, 'sanctum');

        $week = Week::factory()->create(['start_date' => now()->addDay()->toDateString()]);
        $sub = Subscription::factory()->create(['user_id' => $owner->id, 'week_id' => $week->id]);

        $this->deleteJson('/api/subscriptions/' . $sub->id)->assertStatus(403);
    }
}
