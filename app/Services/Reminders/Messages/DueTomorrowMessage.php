<?php

declare(strict_types=1);

namespace App\Services\Reminders\Messages;

use App\Data\UserSnapshot;
use App\Enums\TaskStatus;
use App\Models\Reminder;
use App\Models\Task;
use App\Support\Labels;

final class DueTomorrowMessage implements ReminderMessage
{
    public function __construct(private readonly Labels $labels) {}

    /** A reminder attached to a goal lists only that goal's tasks. */
    public function build(Reminder $reminder, UserSnapshot $context): string
    {
        $tomorrow = $context->today->addDay();
        $due = $context->tasks()->filter(fn (Task $task): bool => ! $task->isRecurring()
            && $task->status !== TaskStatus::Done
            && $task->due_date?->equalTo($tomorrow) === true
            && ($reminder->goal_id === null || $task->goal_id === $reminder->goal_id));

        return implode("\n", [
            'due tomorrow · '.strtolower($tomorrow->format('D, d M')),
            ...($due->isEmpty()
                ? ['nothing due tomorrow']
                : $due->map(fn (Task $task): string => "· {$task->title} · {$this->labels->goalName($task->goal)}")->values()->all()),
        ]);
    }
}
