<?php

declare(strict_types=1);

namespace App\Services\Goals;

use App\Data\Goals\GoalListGroupData;
use App\Data\Goals\GoalListItemData;
use App\Data\Goals\GoalsListData;
use App\Enums\GoalStatus;
use App\Models\Goal;
use App\Models\User;
use App\Support\Labels;
use Illuminate\Support\Collection;

/**
 * Every goal of the owner grouped by status, which is how paused and finished goals are found again.
 */
final class GoalsListService
{
    public function __construct(
        private readonly GoalProgressService $progress,
        private readonly Labels $labels,
    ) {}

    public function for(User $user): GoalsListData
    {
        $goals = $user->goals()->with('tasks.completions')->orderBy('position')->get();

        return new GoalsListData(array_map(
            fn (GoalStatus $status): GoalListGroupData => $this->group($status, $goals),
            GoalStatus::cases(),
        ));
    }

    /**
     * @param  Collection<int, Goal>  $goals
     */
    private function group(GoalStatus $status, Collection $goals): GoalListGroupData
    {
        return new GoalListGroupData(
            $status,
            $goals
                ->where('status', $status)
                ->map(fn (Goal $goal): GoalListItemData => new GoalListItemData(
                    $goal->id,
                    $goal->slug,
                    $goal->title,
                    $goal->type,
                    $goal->priority,
                    $goal->color,
                    $this->labels->shortDate($goal->start_date),
                    $this->labels->shortDate($goal->due_date),
                    $this->progress->progress($goal),
                ))
                ->values()
                ->all(),
        );
    }
}
