<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Completion;
use App\Models\Task;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Completion>
 */
final class CompletionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'user_id' => User::factory(),
            'completed_on' => CarbonImmutable::today(),
            'points' => 1,
        ];
    }

    /** Owner and points follow the task, as CompletionService records them. */
    public function forTask(Task $task): self
    {
        return $this->state([
            'task_id' => $task->id,
            'user_id' => $task->goal->user_id,
            'points' => $task->points,
        ]);
    }

    public function on(CarbonImmutable $day): self
    {
        return $this->state(['completed_on' => $day]);
    }

    public function worth(int $points): self
    {
        return $this->state(['points' => $points]);
    }
}
