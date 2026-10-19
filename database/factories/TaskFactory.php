<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Task> */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->sentence(),
            'taskable_type' => null,
            'taskable_id' => null,
            'assigned_to' => User::factory(),
            'created_by' => null,
            'due_at' => fake()->dateTimeBetween('-5 days', '+10 days'),
            'reminder_at' => null,
            'priority' => fake()->randomElement(Task::PRIORITIES),
            'status' => 'pending',
            'completed_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => 'completed', 'completed_at' => now()]);
    }
}
