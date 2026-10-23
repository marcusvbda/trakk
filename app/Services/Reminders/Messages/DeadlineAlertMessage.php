<?php

declare(strict_types=1);

namespace App\Services\Reminders\Messages;

use App\Data\UserSnapshot;
use App\Enums\TaskStatus;
use App\Models\Goal;
use App\Models\Reminder;
use App\Models\Task;
use App\Support\Labels;

final class DeadlineAlertMessage implements ReminderMessage
{
    public function __construct(private readonly Labels $labels) {}

    public function build(Reminder $reminder, UserSnapshot $context): string
    {
        /** @var Goal|null $goal */
        $goal = $context->goals->firstWhere('id', $reminder->goal_id);

        if ($goal === null) {
            return $reminder->label.' · the goal is not active';
        }

        $days = max(0, (int) $context->today->diffInDays($goal->due_date, false));
        $open = $goal->tasks->filter(fn (Task $task): bool => ! $task->isRecurring() && $task->status !== TaskStatus::Done)->count();

        return implode("\n", [
            $this->labels->goalName($goal).' · '.$days.' '.($days === 1 ? 'day' : 'days').' to the deadline',
            'due '.$this->labels->shortDate($goal->due_date),
            'open tasks '.$open,
        ]);
    }
}
