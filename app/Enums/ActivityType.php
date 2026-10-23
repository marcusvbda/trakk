<?php

declare(strict_types=1);

namespace App\Enums;

enum ActivityType: string
{
    case TaskCompleted = 'task_completed';
    case TaskUndone = 'task_undone';
    case TaskMoved = 'task_moved';
    case TaskReopened = 'task_reopened';
    case TaskCreated = 'task_created';
    case TaskOverdue = 'task_overdue';
    case Replanned = 'replanned';
    case CheckIn = 'check_in';
    case GoalCreated = 'goal_created';
    case DeadlineNear = 'deadline_near';
    case ReminderSent = 'reminder_sent';
    case ReminderFailed = 'reminder_failed';
    case ReminderEnabled = 'reminder_enabled';
    case ReminderPaused = 'reminder_paused';
    case McpCall = 'mcp_call';

    public function logKind(): LogKind
    {
        return match ($this) {
            self::TaskCompleted => LogKind::Done,
            self::CheckIn => LogKind::Checkin,
            self::TaskUndone, self::ReminderPaused => LogKind::Undone,
            self::TaskReopened => LogKind::Reopened,
            self::TaskOverdue, self::DeadlineNear, self::ReminderFailed => LogKind::Warning,
            self::TaskMoved,
            self::ReminderSent,
            self::ReminderEnabled,
            self::GoalCreated,
            self::TaskCreated,
            self::Replanned,
            self::McpCall => LogKind::Sent,
        };
    }
}
