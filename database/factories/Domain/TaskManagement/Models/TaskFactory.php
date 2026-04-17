<?php

namespace Database\Factories\Domain\TaskManagement\Models;

use App\Domain\TaskManagement\Enums\TaskPriority;
use App\Domain\TaskManagement\Enums\TaskStatus;
use App\Domain\TaskManagement\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'priority' => TaskPriority::Medium->value,
            'status' => TaskStatus::Todo->value,
            'due_date' => fake()->dateTimeBetween('-1 week', '+2 weeks')->format('Y-m-d'),
            'estimated_hours' => fake()->randomFloat(2, 2, 20),
        ];
    }
}