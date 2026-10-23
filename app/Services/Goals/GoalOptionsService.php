<?php

declare(strict_types=1);

namespace App\Services\Goals;

use App\Data\Goals\GoalOptionData;
use App\Data\SheetOptionsData;
use App\Models\Goal;
use App\Models\User;
use Carbon\CarbonImmutable;

final class GoalOptionsService
{
    private const int DEFAULT_GOAL_DAYS = 90;

    public function for(User $user, CarbonImmutable $today): SheetOptionsData
    {
        return new SheetOptionsData(
            $today->toDateString(),
            $today->addDays(self::DEFAULT_GOAL_DAYS)->toDateString(),
            $this->goals($user),
        );
    }

    /**
     * @return list<GoalOptionData>
     */
    public function goals(User $user): array
    {
        return $user->goals()
            ->orderBy('position')
            ->get(['id', 'title', 'slug', 'type', 'color', 'status'])
            ->map(fn (Goal $goal): GoalOptionData => new GoalOptionData($goal->id, $goal->title, $goal->slug, $goal->type, $goal->color, $goal->status))
            ->values()
            ->all();
    }
}
