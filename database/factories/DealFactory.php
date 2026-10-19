<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Deal> */
class DealFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3).' deal',
            'company_id' => Company::factory(),
            'contact_id' => null,
            'lead_id' => null,
            'stage_id' => PipelineStage::factory(),
            'owner_id' => User::factory(),
            'created_by' => null,
            'value' => fake()->numberBetween(50_000, 5_000_000),
            'probability' => null,
            'expected_close_date' => fake()->dateTimeBetween('now', '+60 days')->format('Y-m-d'),
            'closed_at' => null,
            'description' => fake()->optional()->sentence(),
        ];
    }
}
