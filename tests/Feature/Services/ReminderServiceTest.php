<?php

declare(strict_types=1);

use App\Data\ReminderData;
use App\Enums\ActivityType;
use App\Enums\ReminderChannel;
use App\Enums\ReminderKind;
use App\Models\Activity;
use App\Models\Goal;
use App\Models\Reminder;
use App\Models\User;
use App\Services\Reminders\ReminderService;
use App\Support\Labels;

beforeEach(function () {
    $this->service = app(ReminderService::class);
    $this->user = User::factory()->create();
});

function reminderData(array $overrides = []): ReminderData
{
    return new ReminderData(...[
        'kind' => ReminderKind::Custom,
        'label' => 'Stretch',
        'time' => '08:15',
        'channels' => [ReminderChannel::Telegram],
        'daysOfWeek' => [1, 3, 5],
        ...$overrides,
    ]);
}

describe('create and update', function () {
    it('creates a recurring reminder', function () {
        $reminder = $this->service->create($this->user, reminderData(['daysOfWeek' => [5, 3, 1, 3]]));

        expect($reminder->days_of_week)->toBe([1, 3, 5])
            ->and($reminder->fire_on)->toBeNull()
            ->and($reminder->channels->all())->toBe([ReminderChannel::Telegram]);
    });

    it('stores an empty weekday list as no weekdays when a date is set', function () {
        $reminder = $this->service->create($this->user, reminderData(['daysOfWeek' => [], 'fireOn' => day('2027-01-01')]));

        expect($reminder->days_of_week)->toBeNull();
    });

    it('creates a one-off reminder for a date', function () {
        $reminder = $this->service->create($this->user, reminderData(['daysOfWeek' => null, 'fireOn' => day('2027-01-01')]));

        expect($reminder->days_of_week)->toBeNull()
            ->and($reminder->fire_on->toDateString())->toBe('2027-01-01')
            ->and((new Labels)->reminderFrequency($reminder))->toBe('01 jan');
    });

    it('attaches a goal reminder to the goal', function () {
        $goal = Goal::factory()->for($this->user)->create();

        $reminder = $this->service->create($this->user, reminderData(['kind' => ReminderKind::PaceSummary, 'goalId' => $goal->id]));

        expect($reminder->goal_id)->toBe($goal->id);
    });

    it('rejects invalid schedules, channels and goals', function (array $overrides) {
        $this->service->create($this->user, reminderData($overrides));
    })->throws(InvalidArgumentException::class)->with([
        'no channel' => [['channels' => []]],
        'neither weekdays nor date' => [['daysOfWeek' => null]],
        'empty weekdays' => [['daysOfWeek' => []]],
        'both weekdays and date' => [['fireOn' => day('2027-01-01')]],
        'weekday out of range' => [['daysOfWeek' => [0]]],
        'goal kind without a goal' => [['kind' => ReminderKind::DeadlineAlert]],
        'impossible time' => [['time' => '25:99']],
        'time without leading zero' => [['time' => '9:05']],
    ]);

    it('rejects a goal of another user', function () {
        $foreign = Goal::factory()->create();

        $this->service->create($this->user, reminderData(['goalId' => $foreign->id]));
    })->throws(InvalidArgumentException::class);

    it('updates every field', function () {
        $reminder = $this->service->create($this->user, reminderData());

        $this->service->update($reminder, reminderData(['label' => 'Walk', 'time' => '18:45', 'channels' => [ReminderChannel::Email, ReminderChannel::Telegram]]));

        expect($reminder->refresh()->label)->toBe('Walk')
            ->and($reminder->time)->toBe('18:45:00')
            ->and($reminder->channels->all())->toBe([ReminderChannel::Email, ReminderChannel::Telegram]);
    });
});

describe('setActive', function () {
    it('logs when a reminder is paused or enabled, and only when it changes', function () {
        $reminder = $this->service->create($this->user, reminderData());

        $this->service->setActive($reminder, true);
        $this->service->setActive($reminder, false);
        $this->service->setActive($reminder, false);
        $this->service->setActive($reminder, true);

        expect(Activity::query()->orderBy('id')->pluck('type')->all())->toBe([ActivityType::ReminderPaused, ActivityType::ReminderEnabled])
            ->and($reminder->refresh()->is_active)->toBeTrue();
    });
});

describe('default reminders', function () {
    it('creates the five defaults', function () {
        $this->service->createDefaults($this->user);

        $labels = new Labels;
        $rows = $this->user->reminders()->orderBy('time')->get()->map(fn (Reminder $reminder): array => [
            $labels->time($reminder->time),
            $reminder->label,
            $labels->reminderFrequency($reminder),
            $labels->reminderChannels($reminder),
            $reminder->is_active,
        ])->all();

        expect($rows)->toBe([
            ['07:30', 'Daily brief', 'daily', 'telegram', true],
            ['09:00', 'Goals off pace', 'monday', 'email', false],
            ['10:00', 'Weekly review', 'sunday', 'telegram · email', true],
            ['18:00', 'Tasks due tomorrow', 'day before', 'telegram', true],
            ['21:30', 'Evening check-in', 'daily', 'telegram', true],
        ]);
    });

    it('does not add them again', function () {
        $this->service->createDefaults($this->user);
        $this->service->createDefaults($this->user);

        expect($this->user->reminders()->count())->toBe(5);
    });

    it('leaves a user who already has reminders alone', function () {
        $this->service->create($this->user, reminderData());

        $this->service->createDefaults($this->user);

        expect($this->user->reminders()->count())->toBe(1);
    });
});
