<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Activity> */
class ActivityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'activityable_type' => Company::class,
            'activityable_id' => Company::factory(),
            'user_id' => User::factory(),
            'type' => fake()->randomElement(Activity::TYPES),
            'subject' => fake()->sentence(3),
            'body' => fake()->paragraph(),
            'happened_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
