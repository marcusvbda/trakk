<?php

declare(strict_types=1);

use App\Enums\ActivityType;
use App\Enums\LogKind;

it('maps every activity to the log kind the dashboard shows', function (ActivityType $type, LogKind $kind) {
    expect($type->logKind())->toBe($kind);
})->with([
    [ActivityType::TaskCompleted, LogKind::Done],
    [ActivityType::CheckIn, LogKind::Checkin],
    [ActivityType::TaskUndone, LogKind::Undone],
    [ActivityType::ReminderPaused, LogKind::Undone],
    [ActivityType::TaskMoved, LogKind::Sent],
    [ActivityType::ReminderSent, LogKind::Sent],
    [ActivityType::ReminderEnabled, LogKind::Sent],
    [ActivityType::GoalCreated, LogKind::Sent],
    [ActivityType::TaskCreated, LogKind::Sent],
    [ActivityType::Replanned, LogKind::Sent],
    [ActivityType::McpCall, LogKind::Sent],
    [ActivityType::TaskReopened, LogKind::Reopened],
    [ActivityType::TaskOverdue, LogKind::Warning],
    [ActivityType::DeadlineNear, LogKind::Warning],
    [ActivityType::ReminderFailed, LogKind::Warning],
]);
