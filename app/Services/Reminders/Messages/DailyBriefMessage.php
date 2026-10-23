<?php

declare(strict_types=1);

namespace App\Services\Reminders\Messages;

use App\Data\Dashboard\TodayActionData;
use App\Data\UserSnapshot;
use App\Models\Reminder;
use App\Services\Dashboard\AgendaBuilder;
use App\Services\Dashboard\FocusProjectService;
use App\Services\Dashboard\TodayListBuilder;

final class DailyBriefMessage implements ReminderMessage
{
    public function __construct(
        private readonly TodayListBuilder $todayList,
        private readonly AgendaBuilder $agenda,
        private readonly FocusProjectService $focusProject,
    ) {}

    public function build(Reminder $reminder, UserSnapshot $context): string
    {
        $tasks = $context->tasks();
        $open = collect($this->todayList->build($tasks, $context->today))
            ->reject(fn (TodayActionData $action): bool => $action->isDone);
        $focus = $this->focusProject->for($context->goals, $context->today);

        $lines = [
            'daily brief · '.strtolower($context->today->format('D, d M')),
            'target '.$context->user->daily_points_target.' pts',
            $open->isEmpty() ? 'no actions to do today' : 'today',
            ...$open->map(fn (TodayActionData $action): string => "· {$action->title} · {$action->timeLabel} · +{$action->points}")->all(),
            'overdue '.count($this->agenda->overdue($tasks, $context->today)),
        ];

        if ($focus !== null) {
            $lines[] = "focus {$focus->title} · {$focus->doneCount}/{$focus->totalCount} · {$focus->percent}%";
        }

        return implode("\n", $lines);
    }
}
