<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Lead> */
class LeadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '03'.fake()->numerify('#########'),
            'company_name' => fake()->company(),
            'source' => fake()->randomElement(Lead::SOURCES),
            'status' => 'new',
            'notes' => fake()->optional()->sentence(),
            'assigned_to' => User::factory(),
            'created_by' => null,
        ];
    }

    public function status(string $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
