<?php

namespace Database\Factories;

use App\Models\Week;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Week>
 */
class WeekFactory extends Factory
{
    protected $model = Week::class;

    public function definition(): array
    {
        $start = now()->startOfWeek();
        return [
            'year' => $this->faker->year(),
            'week_number' => $this->faker->numberBetween(1, 52),
            'start_date' => $start->toDateString(),
            'end_date' => $start->addDays(4)->toDateString(),
        ];
    }
}
