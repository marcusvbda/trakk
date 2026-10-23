<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Data\CommandIndexData;
use App\Models\Goal;
use App\Models\Task;
use App\Models\User;
use App\Services\Goals\GoalOptionsService;
use Carbon\CarbonImmutable;

final class CommandIndexService
{
    public function __construct(
        private readonly GoalOptionsService $goalOptions,
        private readonly TodayListBuilder $todayList,
    ) {}

    public function for(User $user, CarbonImmutable $today): CommandIndexData
    {
        $goals = $user->goals()->active()->with('tasks.completions')->get();
        $goals->each(fn (Goal $goal) => $goal->tasks->each(fn (Task $task) => $task->setRelation('goal', $goal)));

        return new CommandIndexData(
            $this->goalOptions->goals($user),
            $this->todayList->build($goals->flatMap(fn (Goal $goal) => $goal->tasks)->values(), $today),
        );
    }
}
