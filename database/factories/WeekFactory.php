<?php

namespace Database\Factories;

use App\Models\Week;
use Illuminate\Database\Eloquent\Factories\Factory;

class WeekFactory extends Factory
{
    protected $model = Week::class;

    public function definition(): array
    {
        $menuIds = \App\Models\Menu::pluck('id')->toArray();
        if (count($menuIds) < 2) {
            // Ensure at least 2 menu items exist for valid foreign keys
            $menuIds = array_merge($menuIds, \App\Models\Menu::factory()->count(2 - count($menuIds))->create()->pluck('id')->toArray());
        }
        $pick = fn() => fake()->randomElement($menuIds);
        $pickOptional = fn() => fake()->optional()->randomElement($menuIds);
        return [
            'week' => $this->faker->numberBetween(1, 53),
            'soup' => $pick(),
            'day1a' => $pick(),
            'day1b' => $pick(),
            'day1c' => $pickOptional(),
            'day2a' => $pick(),
            'day2b' => $pick(),
            'day2c' => $pickOptional(),
            'day3a' => $pick(),
            'day3b' => $pick(),
            'day3c' => $pickOptional(),
            'day4a' => $pick(),
            'day4b' => $pick(),
            'day4c' => $pickOptional(),
            'day5a' => $pick(),
            'day5b' => $pick(),
            'day5c' => $pickOptional(),
        ];
    }
}
