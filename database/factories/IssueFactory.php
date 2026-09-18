<?php

namespace Database\Factories;

use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Issue>
 */
class IssueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'reporter_id' => User::factory(),
            'assignee_id' => null,
            'number' => fake()->unique()->numberBetween(1, 100000),
            'title' => fake()->sentence(),
            'description' => fake()->optional()->paragraph(),
            'status' => IssueStatus::Todo,
            'priority' => IssuePriority::Medium,
        ];
    }
}
