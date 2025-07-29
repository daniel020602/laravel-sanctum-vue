<?php

namespace Database\Factories;

use App\Models\Sub;
use App\Models\User;
use App\Models\Week;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubFactory extends Factory
{
    protected $model = Sub::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'week_id' => function() {
                $week = Week::factory()->create();
                // For each day, pick one of the available menu IDs (a, b, or c)
                $pick = function($a, $b, $c) {
                    $options = [$a, $b];
                    if ($c !== null) $options[] = $c;
                    return fake()->randomElement($options);
                };
                // Store the chosen menu IDs for each day in the closure's scope
                app()->instance('sub_factory_day1', $pick($week->day1a, $week->day1b, $week->day1c));
                app()->instance('sub_factory_day2', $pick($week->day2a, $week->day2b, $week->day2c));
                app()->instance('sub_factory_day3', $pick($week->day3a, $week->day3b, $week->day3c));
                app()->instance('sub_factory_day4', $pick($week->day4a, $week->day4b, $week->day4c));
                app()->instance('sub_factory_day5', $pick($week->day5a, $week->day5b, $week->day5c));
                return $week->id;
            },
            'day1' => fn() => app('sub_factory_day1'),
            'day2' => fn() => app('sub_factory_day2'),
            'day3' => fn() => app('sub_factory_day3'),
            'day4' => fn() => app('sub_factory_day4'),
            'day5' => fn() => app('sub_factory_day5'),
        ];
    }
}
