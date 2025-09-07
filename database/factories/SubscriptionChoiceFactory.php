<?php

namespace Database\Factories;

use App\Models\SubscriptionChoice;
use App\Models\Subscription;
use App\Models\WeekMenu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionChoice>
 */
class SubscriptionChoiceFactory extends Factory
{
    protected $model = SubscriptionChoice::class;

    public function definition(): array
    {
        return [
            'subscription_id' => Subscription::factory(),
            'week_menu_id' => WeekMenu::factory(),
            'day' => $this->faker->numberBetween(1,5),
        ];
    }
}
