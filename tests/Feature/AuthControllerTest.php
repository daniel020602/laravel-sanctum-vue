<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\Models\Subscription;
use App\Models\SubscriptionChoice;
use App\Models\Week;
use App\Models\WeekMenu;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['user', 'token']);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_user_can_login_with_correct_credentials()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['user', 'token']);
    }

    public function test_user_cannot_login_with_wrong_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(401)
                 ->assertJson(['errors' => ['email' => ['Invalid credentials']]]);
    }

    public function test_user_can_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('TestToken')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}"
        ])->postJson('/api/logout');

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Logged out']);
    }

    public function test_logout_requires_authentication_returns_401()
    {
        // no auth header
        $response = $this->postJson('/api/logout');
        $response->assertStatus(401);
    }

    public function test_user_can_update_phone_and_address()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->putJson('/api/change-data', [
            'phone' => '+3612345678',
            'address' => 'Test Address 123'
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'User data updated successfully']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'phone' => '+3612345678',
            'address' => 'Test Address 123'
        ]);
    }

    public function test_showuser_requires_authentication_returns_401()
    {
        $target = User::factory()->create();
        $res = $this->getJson("/api/auth/show-user/{$target->id}");
        $res->assertStatus(401);
    }

    public function test_showuser_non_admin_returns_403()
    {
        $non = User::factory()->create(['is_admin' => false]);
        $target = User::factory()->create();
        $res = $this->actingAs($non, 'sanctum')->getJson("/api/auth/show-user/{$target->id}");
        $res->assertStatus(403);
    }

    public function test_deleteuser_requires_authentication_returns_401()
    {
        $target = User::factory()->create();
        $res = $this->deleteJson("/api/auth/delete-user/{$target->id}");
        $res->assertStatus(401);
    }

    public function test_deleteuser_non_admin_returns_403()
    {
        $non = User::factory()->create(['is_admin' => false]);
        $target = User::factory()->create();
        $res = $this->actingAs($non, 'sanctum')->deleteJson("/api/auth/delete-user/{$target->id}");
        $res->assertStatus(403);
    }

    public function test_changedata_requires_authentication_returns_401()
    {
        // Disable middleware so the request reaches the controller and triggers the
        // controller's own 401 branch (when $request->user() is null).
        $this->withoutMiddleware();
        $res = $this->putJson('/api/change-data', ['phone' => '+100000']);
        $res->assertStatus(401)->assertJson(['message' => 'Unauthorized']);
    }

    public function test_user_cannot_change_other_user_data_returns_403()
    {
        $non = User::factory()->create(['is_admin' => false]);
        $target = User::factory()->create(['phone' => null, 'address' => null]);
        $res = $this->actingAs($non, 'sanctum')->putJson("/api/auth/change-data/{$target->id}", ['phone' => '+199999']);
        $res->assertStatus(403);
    }

    public function test_changedata_nonexistent_returns_404()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $res = $this->actingAs($admin, 'sanctum')->putJson('/api/auth/change-data/999999', ['phone' => '+101010']);
        $res->assertStatus(404);
    }

    public function test_searchuser_requires_authentication_returns_401()
    {
        $res = $this->getJson('/api/auth/search-user?query=searchtest');
        $res->assertStatus(401);
    }

    public function test_search_user_no_users_returns_404()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        // query that matches no users
        $res = $this->actingAs($admin, 'sanctum')->getJson('/api/auth/search-user?query=nomatch');
        $res->assertStatus(404)->assertJson(['message' => 'No users found']);
    }

    public function test_search_user_no_subscriptions_returns_404()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        // create a user that will match but has no subscriptions
        $u = User::factory()->create(['email' => 'searchme@example.com']);

        $res = $this->actingAs($admin, 'sanctum')->getJson('/api/auth/search-user?query=searchme');
        $res->assertStatus(404)->assertJson(['message' => 'No subscriptions found for the matched users']);
    }

    public function test_search_user_no_subscriptions_for_current_week_returns_404()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        // create a user and attach a subscription for a different week/year
        $u = User::factory()->create(['email' => 'weekuser@example.com']);
        $week = Week::factory()->create(['week_number' => 1, 'year' => 2000]);
        $sub = Subscription::factory()->create(['user_id' => $u->id, 'week_id' => $week->id]);

        $res = $this->actingAs($admin, 'sanctum')->getJson('/api/auth/search-user?query=weekuser');
        $res->assertStatus(404)->assertJson(['message' => 'No subscriptions found for the current week']);
    }

    public function test_promote_nonexistent_returns_404()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $res = $this->actingAs($admin, 'sanctum')->postJson('/api/auth/promote/999999');
        $res->assertStatus(404)->assertJson(['message' => 'User not found']);
    }

    public function test_demote_nonexistent_returns_404()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $res = $this->actingAs($admin, 'sanctum')->postJson('/api/auth/demote/999999');
        $res->assertStatus(404)->assertJson(['message' => 'User not found']);
    }

    public function test_admin_can_list_users_and_non_admin_cannot()
    {
    $admin = User::factory()->create(['is_admin' => true]);

    // create some users
    User::factory()->count(3)->create();

    $res = $this->actingAs($admin, 'sanctum')->getJson('/api/auth/list-users');
    $res->assertStatus(200)->assertJsonStructure(['users']);

    // non-admin cannot
    $user = User::factory()->create(['is_admin' => false]);
    $res2 = $this->actingAs($user, 'sanctum')->getJson('/api/auth/list-users');
    $res2->assertStatus(403);
    }

    public function test_admin_can_show_and_delete_user()
    {
    $admin = User::factory()->create(['is_admin' => true]);
    $target = User::factory()->create();

    $show = $this->actingAs($admin, 'sanctum')->getJson("/api/auth/show-user/{$target->id}");
    $show->assertStatus(200)->assertJsonStructure(['user']);

    $del = $this->actingAs($admin, 'sanctum')->deleteJson("/api/auth/delete-user/{$target->id}");
        $del->assertStatus(200)->assertJson(['message' => 'User deleted successfully']);
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_show_nonexistent_user_returns_404()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $res = $this->actingAs($admin, 'sanctum')->getJson('/api/auth/show-user/999999');
        $res->assertStatus(404);
    }

    public function test_delete_nonexistent_user_returns_404()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $res = $this->actingAs($admin, 'sanctum')->deleteJson('/api/auth/delete-user/999999');
        $res->assertStatus(404);
    }

    public function test_promote_and_demote_user()
    {
    $admin = User::factory()->create(['is_admin' => true]);
    $target = User::factory()->create(['is_admin' => false]);

    $prom = $this->actingAs($admin, 'sanctum')->postJson("/api/auth/promote/{$target->id}");
    $prom->assertStatus(200)->assertJson(['message' => 'User promoted to admin successfully']);
    $this->assertDatabaseHas('users', ['id' => $target->id, 'is_admin' => true]);

    $dem = $this->actingAs($admin, 'sanctum')->postJson("/api/auth/demote/{$target->id}");
        $dem->assertStatus(200)->assertJson(['message' => 'User demoted to regular user successfully']);
        $this->assertDatabaseHas('users', ['id' => $target->id, 'is_admin' => false]);
    }

    public function test_non_admin_cannot_promote_or_demote_returns_403()
    {
        $non = User::factory()->create(['is_admin' => false]);
        $target = User::factory()->create();

        $p = $this->actingAs($non, 'sanctum')->postJson("/api/auth/promote/{$target->id}");
        $p->assertStatus(403);

        $d = $this->actingAs($non, 'sanctum')->postJson("/api/auth/demote/{$target->id}");
        $d->assertStatus(403);
    }

    public function test_admin_can_change_other_user_data()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $target = User::factory()->create(['phone' => null, 'address' => null]);

        $res = $this->actingAs($admin, 'sanctum')->putJson("/api/auth/change-data/{$target->id}", [
            'phone' => '+441234567890',
            'address' => 'Admin set address'
        ]);
        $res->assertStatus(200)->assertJson(['message' => 'User data updated successfully']);
        $this->assertDatabaseHas('users', ['id' => $target->id, 'phone' => '+441234567890', 'address' => 'Admin set address']);
    }

    public function test_search_user_returns_subscriptions_and_choices()
    {
    $admin = User::factory()->create(['is_admin' => true]);

        // user with email starting 'searchtest'
        $u = User::factory()->create(['email' => 'searchtest@example.com']);

        $week = Week::factory()->create(['week_number' => now()->weekOfYear, 'year' => now()->year]);
        $sub = Subscription::factory()->create(['user_id' => $u->id, 'week_id' => $week->id]);
        // create choices linked to subscription
        SubscriptionChoice::factory()->count(2)->create(['subscription_id' => $sub->id]);

    $res = $this->actingAs($admin, 'sanctum')->getJson('/api/auth/search-user?query=searchtest');
        $res->assertStatus(200)->assertJsonStructure(['users', 'subscriptions', 'choices']);
    }

    public function test_search_user_requires_query_and_admin()
    {
    $admin = User::factory()->create(['is_admin' => true]);

    $res = $this->actingAs($admin, 'sanctum')->getJson('/api/auth/search-user');
        $res->assertStatus(400);
    $non = User::factory()->create(['is_admin' => false]);
    $res2 = $this->actingAs($non, 'sanctum')->getJson('/api/auth/search-user?query=foo');
    $res2->assertStatus(403);
    }
}
