<?php

namespace Tests\Unit;

use App\Models\User;
use App\Policies\ReservationPolicy;
use Illuminate\Auth\Access\Response;
use Tests\TestCase;

class ReservationPolicyTest extends TestCase
{
    public function test_admin_allows_admin_user()
    {
        $admin = User::factory()->make(['is_admin' => true]);
        $policy = new ReservationPolicy();
        $result = $policy->admin($admin);
        $this->assertTrue($result->allowed());
    }

    public function test_admin_denies_non_admin_user()
    {
        $user = User::factory()->make(['is_admin' => false]);
        $policy = new ReservationPolicy();
        $result = $policy->admin($user);
        $this->assertFalse($result->allowed());
        $this->assertEquals('this action is reserved for administrators only.', $result->message());
    }
}
