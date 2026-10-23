<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TaskStatus;
use App\Models\Goal;
use App\Models\Task;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
final class TaskFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'goal_id' => Goal::factory(),
            'title' => fake()->unique()->sentence(3),
            'notes' => null,
            'status' => TaskStatus::Todo,
            'points' => fake()->numberBetween(1, 5),
            'due_date' => null,
            'scheduled_time' => null,
            'days_of_week' => null,
            'tag' => null,
            'position' => 0,
            'completed_at' => null,
        ];
    }

    /**
     * @param  list<int>  $days  ISO weekdays, 1 = Monday
     */
    public function recurring(array $days = [1, 2, 3, 4, 5, 6, 7], ?string $time = null): self
    {
        return $this->state(['days_of_week' => $days, 'scheduled_time' => $time, 'due_date' => null]);
    }

    public function dueOn(CarbonImmutable $date): self
    {
        return $this->state(['due_date' => $date, 'days_of_week' => null]);
    }

    public function overdueBy(int $days, CarbonImmutable $today): self
    {
        return $this->dueOn($today->subDays($days));
    }

    public function points(int $points): self
    {
        return $this->state(['points' => $points]);
    }

    public function doing(): self
    {
        return $this->state(['status' => TaskStatus::Doing]);
    }

    public function done(?CarbonImmutable $at = null): self
    {
        return $this->state([
            'status' => TaskStatus::Done,
            'completed_at' => $at ?? CarbonImmutable::now(),
        ]);
    }
}
