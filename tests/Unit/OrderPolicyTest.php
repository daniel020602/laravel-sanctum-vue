<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Order;
use App\Policies\OrderPolicy;
use Illuminate\Auth\Access\Response;
use Tests\TestCase;

class OrderPolicyTest extends TestCase
{
    public function test_admin_allows_admin_user()
    {
        $admin = User::factory()->make(['is_admin' => true]);
        $policy = new OrderPolicy();
        $result = $policy->admin($admin);
        $this->assertTrue($result->allowed());
    }

    public function test_admin_denies_non_admin_user()
    {
        $user = User::factory()->make(['is_admin' => false]);
        $policy = new OrderPolicy();
        $result = $policy->admin($user);
        $this->assertFalse($result->allowed());
        $this->assertEquals('You do not have admin access.', $result->message());
    }

    public function test_ownerOrAdmin_allows_owner()
    {
        $user = User::factory()->make(['id' => 1, 'is_admin' => false]);
        $order = Order::factory()->make(['user_id' => 1]);
        $policy = new OrderPolicy();
        $result = $policy->ownerOrAdmin($user, $order);
        $this->assertTrue($result->allowed());
    }

    public function test_ownerOrAdmin_allows_admin()
    {
        $admin = User::factory()->make(['id' => 2, 'is_admin' => true]);
        $order = Order::factory()->make(['user_id' => 1]);
        $policy = new OrderPolicy();
        $result = $policy->ownerOrAdmin($admin, $order);
        $this->assertTrue($result->allowed());
    }

    public function test_ownerOrAdmin_denies_non_owner_non_admin()
    {
        $user = User::factory()->make(['id' => 2, 'is_admin' => false]);
        $order = Order::factory()->make(['user_id' => 1]);
        $policy = new OrderPolicy();
        $result = $policy->ownerOrAdmin($user, $order);
        $this->assertFalse($result->allowed());
        $this->assertEquals('You do not own this order.', $result->message());
    }
}
