<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\GoalType;
use App\Enums\ReminderChannel;
use App\Enums\ReminderKind;
use App\Models\Goal;
use App\Models\Reminder;
use App\Models\Task;
use Carbon\CarbonImmutable;

final class Labels
{
    private const array WEEKDAYS = [1 => 'monday', 2 => 'tuesday', 3 => 'wednesday', 4 => 'thursday', 5 => 'friday', 6 => 'saturday', 7 => 'sunday'];

    public function shortDate(CarbonImmutable $date): string
    {
        return strtolower($date->format('d M'));
    }

    public function fullDate(CarbonImmutable $date): string
    {
        return strtolower($date->format('d M Y'));
    }

    public function dayLabel(CarbonImmutable $date): string
    {
        return strtoupper($date->format('D d'));
    }

    public function time(string $time): string
    {
        return substr($time, 0, 5);
    }

    public function topBarDate(CarbonImmutable $today): string
    {
        return sprintf(
            '%s · wk %d · day %d/%d',
            strtolower($today->format('D, d M Y')),
            $today->isoWeek,
            $today->dayOfYear,
            $today->daysInYear,
        );
    }

    public function logTime(CarbonImmutable $occurredAt, string $timezone, CarbonImmutable $today): string
    {
        $local = $occurredAt->setTimezone($timezone);
        $day = CarbonImmutable::parse($local->toDateString());

        return match (true) {
            $day->equalTo($today) => $local->format('H:i'),
            $day->equalTo($today->subDay()) => 'yday '.$local->format('H:i'),
            default => $this->shortDate($day),
        };
    }

    public function todayTime(Task $task, CarbonImmutable $today): string
    {
        return match (true) {
            $task->scheduled_time !== null => $this->time($task->scheduled_time),
            $task->isRecurring() => 'all day',
            $task->due_date?->equalTo($today) === true => 'today',
            default => 'anytime',
        };
    }

    public function goalFrequency(Goal $goal): string
    {
        return match (true) {
            $goal->type === GoalType::Project => 'no frequency',
            $goal->target_per_week === 7 => 'daily',
            default => $goal->target_per_week.'x/wk',
        };
    }

    public function reminderFrequency(Reminder $reminder): string
    {
        if ($reminder->fire_on !== null) {
            return $this->shortDate($reminder->fire_on);
        }

        if ($reminder->kind === ReminderKind::DueTomorrow) {
            return 'day before';
        }

        $days = $reminder->days_of_week ?? [];
        sort($days);

        return match (true) {
            $days === [1, 2, 3, 4, 5, 6, 7] => 'daily',
            $days === [1, 2, 3, 4, 5] => 'weekdays',
            count($days) === 1 => self::WEEKDAYS[$days[0]],
            default => implode(', ', array_map(fn (int $day): string => substr(self::WEEKDAYS[$day], 0, 3), $days)),
        };
    }

    public function reminderChannels(Reminder $reminder): string
    {
        return $reminder->channels->map(fn (ReminderChannel $channel): string => $channel->value)->implode(' · ');
    }
}
