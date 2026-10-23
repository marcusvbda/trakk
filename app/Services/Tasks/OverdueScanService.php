<?php

declare(strict_types=1);

namespace App\Services\Tasks;

use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\Task;
use App\Models\User;
use App\Services\Activity\ActivityLogger;
use Carbon\CarbonImmutable;

final class OverdueScanService
{
    public function __construct(private readonly ActivityLogger $activity) {}

    /**
     * Logs a `task_overdue` warning for each overdue task of an active goal, once per due date, so running the
     * scan again (or replanning to a new date that also passes) never repeats the same warning.
     *
     * @return int how many warnings were logged
     */
    public function scan(User $user, CarbonImmutable $today): int
    {
        $logged = 0;

        $tasks = Task::query()
            ->with('goal')
            ->ownedBy($user)
            ->whereHas('goal', fn ($goals) => $goals->active())
            ->overdue($today)
            ->get();

        foreach ($tasks as $task) {
            if ($this->isLogged($task)) {
                continue;
            }

            $this->activity->taskOverdue($task);
            $logged++;
        }

        return $logged;
    }

    private function isLogged(Task $task): bool
    {
        return Activity::query()
            ->where('type', ActivityType::TaskOverdue)
            ->where('task_id', $task->id)
            ->where('meta->due', $task->due_date?->toDateString())
            ->exists();
    }
}
