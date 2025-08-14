<?php

namespace Tests\Unit;

use App\Rules\ValidDayValue;
use App\Models\Week;
use App\Models\Menu;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidDayValueTest extends TestCase
{
    use RefreshDatabase;
    protected $soup;
    protected $menuIds;
    protected $week;

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
    }
    public function test_valid_day_value_passes()
    {
        $rule = new ValidDayValue('day1', $this->week->id);
        $validator = Validator::make([
            'day1' => $this->menuIds[0],
            'week_id' => $this->week->id,
        ], [
            'day1' => [$rule],
        ]);
        $this->assertTrue($validator->passes());
    }

    public function test_invalid_day_value_fails()
    {
        $menu = Menu::factory()->create();

        // Létrehozunk egy teljesen másik menüt, és azt tesszük be a hétbe
        $otherMenu = Menu::factory()->create();

        $week = Week::factory()->create([
            'day1a' => $otherMenu->id, // teljesen más ID, garantáltan nem egyezik
        ]);

        $rule = new ValidDayValue('day1', $week->id);

        $validator = Validator::make([
            'day1' => $menu->id, // ezt próbáljuk beírni
            'week_id' => $week->id,
        ], [
            'day1' => [$rule],
        ]);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('day1', $validator->errors()->messages());
    }


    public function test_fails_for_past_week()
    {
        $menu = Menu::factory()->create();
        $week = Week::factory()->create([
            'week' => now()->weekOfYear - 1,
            'day1a' => $menu->id,
        ]);
        $rule = new ValidDayValue('day1', $week->id);
        $validator = Validator::make([
            'day1' => $menu->id,
            'week_id' => $week->id,
        ], [
            'day1' => [$rule],
        ]);
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('day1', $validator->errors()->messages());
    }

    public function test_fails_for_missing_week()
    {
        $rule = new ValidDayValue('day1', 9999);
        $validator = Validator::make([
            'day1' => 1,
            'week_id' => 9999,
        ], [
            'day1' => [$rule],
        ]);
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('day1', $validator->errors()->messages());
    }
    public function test_fails_for_missing_week_id()
    {
        $menu = Menu::factory()->create();
        $rule = new ValidDayValue('day1', null); // no weekId passed
        $validator = Validator::make([
            'day1' => $menu->id,
            // 'week_id' => missing
        ], [
            'day1' => [$rule],
        ]);
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('day1', $validator->errors()->messages());
        $this->assertStringContainsString('Week ID is required', $validator->errors()->first('day1'));
    }

    public function test_fails_for_invalid_menu_value()
    {
        $menu = Menu::factory()->create();
        $week = Week::factory()->create([
            'week' => now()->weekOfYear, // fontos!
            'day1a' => $menu->id + 1,
            'day1b' => $menu->id + 2,
            'day1c' => $menu->id + 3,
        ]);

        $rule = new ValidDayValue('day1', $week->id);

        $validator = Validator::make([
            'day1' => $menu->id,
            'week_id' => $week->id,
        ], [
            'day1' => [$rule],
        ]);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('day1', $validator->errors()->messages());
        $this->assertStringContainsString('The selected value for day1 is invalid', $validator->errors()->first('day1'));
    }

}
