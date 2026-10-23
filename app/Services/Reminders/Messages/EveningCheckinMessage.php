<?php

declare(strict_types=1);

namespace App\Services\Reminders\Messages;

use App\Data\Dashboard\TodayActionData;
use App\Data\UserSnapshot;
use App\Models\Reminder;
use App\Services\Dashboard\TodayListBuilder;
use App\Services\Scoring\ScoreService;

final class EveningCheckinMessage implements ReminderMessage
{
    public function __construct(
        private readonly TodayListBuilder $todayList,
        private readonly ScoreService $scores,
    ) {}

    public function build(Reminder $reminder, UserSnapshot $context): string
    {
        $target = $context->user->daily_points_target;
        $score = $this->scores->dayScore($context->completions, $context->today, $target);
        $open = collect($this->todayList->build($context->tasks(), $context->today))
            ->reject(fn (TodayActionData $action): bool => $action->isDone);

        return implode("\n", [
            'evening check-in',
            $score->isTargetHit()
                ? "target hit · {$score->points}/{$target} pts"
                : "{$score->points}/{$target} pts · ".($target - $score->points).' to go',
            $open->isEmpty() ? 'nothing left open today' : 'still open',
            ...$open->map(fn (TodayActionData $action): string => "· {$action->title} · {$action->timeLabel} · +{$action->points}")->all(),
            rtrim((string) config('app.url'), '/'),
        ]);
    }
}
