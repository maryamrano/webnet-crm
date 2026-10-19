<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Contact> */
class ContactFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->name(),
            'designation' => fake()->randomElement(['Owner', 'Procurement Manager', 'IT Head', 'Operations Manager', 'Director']),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '03'.fake()->numerify('#########'),
            'is_primary' => false,
        ];
    }
}
