<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Data\UserSnapshot;
use App\Models\Goal;
use App\Models\Task;
use App\Models\User;
use Carbon\CarbonImmutable;

final class UserSnapshotBuilder
{
    public function for(User $user, CarbonImmutable $today): UserSnapshot
    {
        $goals = $user->goals()->active()->with('tasks.completions')->orderBy('position')->get();
        $goals->each(fn (Goal $goal) => $goal->tasks->each(fn (Task $task) => $task->setRelation('goal', $goal)));

        return new UserSnapshot($user, $today, $goals, $user->completions()->get());
    }
}
