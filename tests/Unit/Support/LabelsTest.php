<?php

declare(strict_types=1);

use App\Enums\GoalType;
use App\Enums\ReminderChannel;
use App\Enums\ReminderKind;
use App\Models\Goal;
use App\Models\Reminder;
use App\Models\Task;
use App\Support\Labels;

beforeEach(function () {
    $this->labels = new Labels;
    $this->today = day('2026-10-23');
});

function reminderWith(array $attributes): Reminder
{
    return new Reminder($attributes + [
        'kind' => ReminderKind::DailyBrief,
        'days_of_week' => [1, 2, 3, 4, 5, 6, 7],
        'channels' => [ReminderChannel::Telegram],
    ]);
}

describe('dates', function () {
    it('formats short, full, day and top bar labels', function () {
        expect($this->labels->shortDate(day('2026-07-01')))->toBe('01 jul')
            ->and($this->labels->fullDate(day('2027-01-15')))->toBe('15 jan 2027')
            ->and($this->labels->dayLabel(day('2026-10-24')))->toBe('SAT 24')
            ->and($this->labels->time('07:30:00'))->toBe('07:30')
            ->and($this->labels->topBarDate($this->today))->toBe('fri, 23 oct 2026 · wk 43 · day 296/365');
    });
});

describe('log time', function () {
    it('shows the time today, yday plus time yesterday and the date before that', function (string $occurredAt, string $expected) {
        expect($this->labels->logTime(day($occurredAt), 'Europe/Dublin', $this->today))->toBe($expected);
    })->with([
        'today' => ['2026-10-23 12:10:00', '13:10'],
        'yesterday' => ['2026-10-22 20:34:00', 'yday 21:34'],
        'earlier' => ['2026-10-20 10:00:00', '20 oct'],
        'late utc evening is already tomorrow in dublin' => ['2026-10-22 23:30:00', '00:30'],
    ]);
});

describe('today time', function () {
    it('picks the label for how the action is scheduled', function (array $attributes, string $expected) {
        expect($this->labels->todayTime(new Task($attributes), $this->today))->toBe($expected);
    })->with([
        'scheduled time' => [['scheduled_time' => '07:00:00', 'days_of_week' => [1]], '07:00'],
        'one-off due today' => [['due_date' => '2026-10-23'], 'today'],
        'recurring without time' => [['days_of_week' => [1, 2]], 'all day'],
        'doing without due date' => [['due_date' => null], 'anytime'],
    ]);
});

describe('goal frequency', function () {
    it('says daily, times per week or no frequency', function (GoalType $type, ?int $perWeek, string $expected) {
        expect($this->labels->goalFrequency(new Goal(['type' => $type, 'target_per_week' => $perWeek])))->toBe($expected);
    })->with([
        'five a week' => [GoalType::Habit, 5, '5x/wk'],
        'every day' => [GoalType::Habit, 7, 'daily'],
        'project' => [GoalType::Project, null, 'no frequency'],
    ]);
});

describe('reminder labels', function () {
    it('describes when a reminder repeats', function (array $attributes, string $expected) {
        expect($this->labels->reminderFrequency(reminderWith($attributes)))->toBe($expected);
    })->with([
        'every day' => [['days_of_week' => [1, 2, 3, 4, 5, 6, 7]], 'daily'],
        'weekdays' => [['days_of_week' => [5, 4, 3, 2, 1]], 'weekdays'],
        'one day' => [['days_of_week' => [7]], 'sunday'],
        'a few days' => [['days_of_week' => [1, 3, 5]], 'mon, wed, fri'],
        'day before' => [['kind' => ReminderKind::DueTomorrow], 'day before'],
        'once' => [['fire_on' => '2027-01-01', 'days_of_week' => null], '01 jan'],
    ]);

    it('joins the channels', function () {
        $reminder = reminderWith(['channels' => [ReminderChannel::Telegram, ReminderChannel::Email]]);

        expect($this->labels->reminderChannels($reminder))->toBe('telegram · email');
    });
});
