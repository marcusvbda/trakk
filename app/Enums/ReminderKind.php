<?php

declare(strict_types=1);

namespace App\Enums;

enum ReminderKind: string
{
    case DailyBrief = 'daily_brief';
    case EveningCheckin = 'evening_checkin';
    case DueTomorrow = 'due_tomorrow';
    case WeeklyReview = 'weekly_review';
    case OffPace = 'off_pace';
    case PaceSummary = 'pace_summary';
    case DeadlineAlert = 'deadline_alert';
    case Custom = 'custom';

    public function isGoalScoped(): bool
    {
        return $this === self::PaceSummary || $this === self::DeadlineAlert;
    }
}
