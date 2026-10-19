<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Company> */
class CompanyFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'industry' => fake()->randomElement(['Textiles', 'IT Services', 'Food & Beverage', 'Pharmaceuticals', 'Logistics', 'Retail']),
            'website' => 'https://'.fake()->domainName(),
            'email' => fake()->companyEmail(),
            'phone' => '03'.fake()->numerify('#########'),
            'address' => fake()->streetAddress(),
            'city' => fake()->randomElement(['Karachi', 'Lahore', 'Islamabad', 'Hyderabad', 'Faisalabad']),
            'owner_id' => User::factory(),
            'created_by' => null,
        ];
    }
}
