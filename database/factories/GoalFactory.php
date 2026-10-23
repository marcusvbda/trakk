<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\GoalColor;
use App\Enums\GoalStatus;
use App\Enums\GoalType;
use App\Enums\Priority;
use App\Models\Goal;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Goal>
 */
final class GoalFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);
        $today = CarbonImmutable::today();

        return [
            'user_id' => User::factory(),
            'related_goal_id' => null,
            'title' => $title,
            'slug' => Str::slug($title),
            'icon' => null,
            'description' => fake()->sentence(),
            'done_criteria' => fake()->sentence(),
            'type' => GoalType::Project,
            'color' => fake()->randomElement(GoalColor::cases()),
            'priority' => Priority::Medium,
            'start_date' => $today->subDays(30),
            'due_date' => $today->addDays(70),
            'target_per_week' => null,
            'area' => null,
            'status' => GoalStatus::Active,
            'position' => 0,
        ];
    }

    public function habit(int $targetPerWeek = 5): self
    {
        return $this->state(['type' => GoalType::Habit, 'target_per_week' => $targetPerWeek]);
    }

    public function project(): self
    {
        return $this->state(['type' => GoalType::Project, 'target_per_week' => null]);
    }

    public function window(CarbonImmutable $start, CarbonImmutable $due): self
    {
        return $this->state(['start_date' => $start, 'due_date' => $due]);
    }

    /** Most of the window has elapsed, so a goal without progress is behind. */
    public function behindPace(): self
    {
        $today = CarbonImmutable::today();

        return $this->window($today->subDays(80), $today->addDays(20));
    }

    public function status(GoalStatus $status): self
    {
        return $this->state(['status' => $status]);
    }

    public function paused(): self
    {
        return $this->status(GoalStatus::Paused);
    }

    public function done(): self
    {
        return $this->status(GoalStatus::Done);
    }
}
