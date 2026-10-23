<?php

declare(strict_types=1);

use App\Enums\GoalStatus;
use App\Enums\ReminderKind;
use App\Models\Goal;
use App\Models\Reminder;
use App\Models\Task;
use App\Services\Reminders\ReminderMessageBuilder;

beforeEach(function () {
    $this->owner = seedDemoOwner();
    $this->today = day('2026-10-23');
});

function messageFor(ReminderKind $kind, $today): string
{
    return app(ReminderMessageBuilder::class)->build(Reminder::query()->where('kind', $kind)->firstOrFail(), $today);
}

describe('the demo owner\'s messages', function () {
    it('briefs the morning with what is open, what is overdue and the focus project', function () {
        expect(messageFor(ReminderKind::DailyBrief, $this->today))->toBe(implode("\n", [
            'daily brief · fri, 23 oct',
            'target 14 pts',
            'today',
            '· Reading · 20 pages · 22:00 · +1',
            '· Book sworn translation · today · +4',
            '· Request apostilled marriage certificate · today · +3',
            '· Review moving budget · anytime · +2',
            'overdue 4',
            'focus nomad visa · 9/20 · 45%',
        ]));
    });

    it('closes the evening with the points so far and what is still open', function () {
        expect(messageFor(ReminderKind::EveningCheckin, $this->today))
            ->toContain("evening check-in\n9/14 pts · 5 to go\nstill open")
            ->toContain('· Review moving budget · anytime · +2');
    });

    it('lists what is due tomorrow with its goal', function () {
        expect(messageFor(ReminderKind::DueTomorrow, $this->today))
            ->toBe("due tomorrow · sat, 24 oct\n· Health insurance, no co-pay · nomad visa");
    });

    it('reviews the week with the streak and the goals that are behind', function () {
        expect(messageFor(ReminderKind::WeeklyReview, $this->today))->toBe(implode("\n", [
            'weekly review',
            'week 84 pts · -1% vs last week',
            'streak 12 days · record 21',
            'goals on pace 4/6',
            '· nomad visa -13%',
            '· read 12 books in 2026 -6%',
        ]));
    });

    it('lists the goals off pace, the furthest behind first', function () {
        expect(messageFor(ReminderKind::OffPace, $this->today))
            ->toBe("goals off pace\n· nomad visa · behind -13%\n· read 12 books in 2026 · behind -6%");
    });

    it('summarises one goal\'s pace', function () {
        expect(messageFor(ReminderKind::PaceSummary, $this->today))->toBe(implode("\n", [
            'nomad visa · pace',
            'progress 45% · expected 58% · behind -13%',
            'current 0.6 tasks/wk · required 0.9 tasks/wk',
            'projected 78% at the due date',
        ]));
    });

    it('warns of a goal\'s deadline with its open tasks', function () {
        expect(messageFor(ReminderKind::DeadlineAlert, $this->today))
            ->toBe("nomad visa · 84 days to the deadline\ndue 15 jan\nopen tasks 11");
    });
});

describe('edge cases', function () {
    it('sends a custom reminder\'s label as it is', function () {
        $reminder = Reminder::factory()->for($this->owner)->create(['kind' => ReminderKind::Custom, 'label' => 'Stretch now']);

        expect(app(ReminderMessageBuilder::class)->build($reminder, $this->today))->toBe('Stretch now');
    });

    it('keeps a goal\'s due-tomorrow reminder to that goal', function () {
        $diet = Goal::query()->where('slug', '!=', 'nomad-visa')->where('type', 'project')->firstOrFail();
        Task::factory()->for($diet)->dueOn($this->today->addDay())->create(['title' => 'Other goal task']);
        $reminder = Reminder::query()->where('kind', ReminderKind::DueTomorrow)->firstOrFail();
        $reminder->update(['goal_id' => Goal::query()->where('slug', 'nomad-visa')->value('id')]);

        expect(app(ReminderMessageBuilder::class)->build($reminder->fresh(), $this->today))
            ->toContain('Health insurance')
            ->not->toContain('Other goal task');
    });

    it('says so when nothing is due tomorrow', function () {
        Task::query()->whereDate('due_date', $this->today->addDay())->delete();

        expect(messageFor(ReminderKind::DueTomorrow, $this->today))->toContain('nothing due tomorrow');
    });

    it('says the goal is not active instead of failing when it was paused', function () {
        Goal::query()->where('slug', 'nomad-visa')->update(['status' => GoalStatus::Paused]);

        expect(messageFor(ReminderKind::PaceSummary, $this->today))->toContain('the goal is not active');
    });

    it('says every goal is on pace when none is behind', function () {
        Goal::query()->update(['start_date' => $this->today->addDay(), 'due_date' => $this->today->addDays(100)]);

        expect(messageFor(ReminderKind::OffPace, $this->today))->toBe("goals off pace\nevery goal is on pace");
    });
});
