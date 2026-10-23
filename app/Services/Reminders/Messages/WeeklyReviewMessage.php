<?php

declare(strict_types=1);

namespace App\Services\Reminders\Messages;

use App\Data\UserSnapshot;
use App\Models\Goal;
use App\Models\Reminder;
use App\Services\Goals\GoalProgressService;
use App\Services\Scoring\ScoreService;
use App\Services\Scoring\StreakService;
use App\Support\Labels;

final class WeeklyReviewMessage implements ReminderMessage
{
    public function __construct(
        private readonly ScoreService $scores,
        private readonly StreakService $streaks,
        private readonly GoalProgressService $progress,
        private readonly Labels $labels,
    ) {}

    public function build(Reminder $reminder, UserSnapshot $context): string
    {
        $week = $this->scores->weekScore($context->completions, $context->today);
        $streak = $this->streaks->streak($context->completions, $context->today, $context->user->daily_points_target);
        $behind = $context->goals->filter(fn (Goal $goal): bool => $this->progress->pace($goal, $context->today)->delta < 0);

        return implode("\n", [
            'weekly review',
            "week {$week->points} pts · ".($week->changePercent === null
                ? 'no data last week'
                : sprintf('%+d%% vs last week', $week->changePercent)),
            "streak {$streak->current} days · record {$streak->record}",
            'goals on pace '.($context->goals->count() - $behind->count()).'/'.$context->goals->count(),
            ...$behind->map(fn (Goal $goal): string => '· '.$this->labels->goalName($goal).' '.$this->delta($goal, $context))->values()->all(),
        ]);
    }

    private function delta(Goal $goal, UserSnapshot $context): string
    {
        return sprintf('%+d%%', $this->progress->pace($goal, $context->today)->delta);
    }
}
