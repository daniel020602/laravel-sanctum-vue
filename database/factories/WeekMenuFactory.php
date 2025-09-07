<?php

namespace Database\Factories;

use App\Models\WeekMenu;
use App\Models\Menu;
use App\Models\Week;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WeekMenu>
 */
class WeekMenuFactory extends Factory
{
    protected $model = WeekMenu::class;

    public function definition(): array
    {
        return [
            'week_id' => Week::factory(),
            'menu_id' => Menu::factory(),
            'day_of_week' => $this->faker->numberBetween(1,5),
            'option' => $this->faker->randomElement(['soup','a','b','c']),
        ];
    }
}
