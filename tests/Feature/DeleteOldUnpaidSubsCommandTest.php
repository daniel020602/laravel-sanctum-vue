<?php

namespace Tests\Feature;

use App\Models\Sub;
use App\Models\Week;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;
use App\Models\Menu;
use App\Models\User;

class DeleteOldUnpaidSubsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected $soup;
    protected $menuIds;
    protected $week;
    protected $user;
    protected $otherWeek;

    protected function setUp(): void
    {
        parent::setUp();

        $this->soup = Menu::factory()->create(['type' => 'soup']);
        $this->menuIds = Menu::factory()->count(3)->create(['type' => 'main'])->pluck('id')->toArray();

        $this->week = Week::factory()->create([
            'week' => now()->weekOfYear,
            'soup' => $this->soup->id,
            'day1a' => $this->menuIds[0],
            'day1b' => $this->menuIds[1],
            'day2a' => $this->menuIds[2],
            'day2b' => $this->menuIds[0],
            'day3a' => $this->menuIds[1],
            'day3b' => $this->menuIds[2],
            'day4a' => $this->menuIds[0],
            'day4b' => $this->menuIds[1],
            'day5a' => $this->menuIds[2],
            'day5b' => $this->menuIds[0],
        ]);
        $this->otherWeek = Week::factory()->create(['week' => 999]);
    }
    protected function createSub($user)
    {
        return Sub::factory()->create([
            'user_id' => $user,
            'week_id' => $this->week->id,
            'day1' => $this->week->day1a,
            'day2' => $this->week->day2a,
            'day3' => $this->week->day3a,
            'day4' => $this->week->day4a,
            'day5' => $this->week->day5a,
        ]);
    }
    public function test_deletes_unpaid_subs_for_current_week()
    {
        $user = User::factory()->create();
        $sub = $this->createSub($user);
        $this->assertDatabaseHas('subs', ['id' => $sub->id]);

        Artisan::call('app:delete-old-unpaid-subs');

        $this->assertDatabaseMissing('subs', ['id' => $sub->id]);
    }

    public function test_does_not_delete_paid_subs()
    {
        $user = User::factory()->create();
        $sub = $this->createSub($user);
        $sub->status = 'paid';
        $sub->save();
        $this->assertDatabaseHas('subs', ['id' => $sub->id]);

        Artisan::call('app:delete-old-unpaid-subs');

        $this->assertDatabaseHas('subs', ['id' => $sub->id]);
    }

    public function test_does_nothing_if_no_week_found()
    {
        $user = User::factory()->create();
        $sub = $this->createSub($user);
        $sub->week_id = $this->otherWeek->id;
        $sub->save();

        $this->assertDatabaseHas('subs', ['id' => $sub->id]);

        Artisan::call('app:delete-old-unpaid-subs');

        $this->assertDatabaseHas('subs', ['id' => $sub->id]);
    }
}
