<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\PipelineStage> */
class PipelineStageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'position' => fake()->unique()->numberBetween(10, 999), // seeded stages occupy positions 1-6
            'probability' => fake()->numberBetween(0, 100),
            'is_won' => false,
            'is_lost' => false,
        ];
    }
}
