<?php

declare(strict_types=1);

use App\Enums\ReminderChannel;
use App\Enums\ReminderKind;
use App\Models\Goal;
use App\Models\Reminder;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(day('2026-10-23')->setTime(12, 0));
    $this->user = User::factory()->create();
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function reminderInput(array $overrides = []): array
{
    return [
        'kind' => 'custom',
        'label' => 'Stretch',
        'time' => '08:15',
        'days_of_week' => [1, 3, 5],
        'fire_on' => null,
        'goal_id' => null,
        'channels' => ['telegram'],
        'is_active' => true,
        ...$overrides,
    ];
}

describe('creating a reminder', function () {
    it('stores a recurring reminder for the owner', function () {
        $this->actingAs($this->user)->post(route('reminders.store'), reminderInput(['days_of_week' => [5, 1, 3], 'channels' => ['telegram', 'email']]))->assertRedirect();

        $reminder = $this->user->reminders()->sole();

        expect($reminder)
            ->kind->toBe(ReminderKind::Custom)
            ->label->toBe('Stretch')
            ->days_of_week->toBe([1, 3, 5])
            ->fire_on->toBeNull()
            ->is_active->toBeTrue()
            ->and($reminder->channels->all())->toBe([ReminderChannel::Telegram, ReminderChannel::Email]);
    });

    it('stores a once-only reminder on a date', function () {
        $this->actingAs($this->user)->post(route('reminders.store'), reminderInput(['days_of_week' => null, 'fire_on' => '2027-01-01']));

        expect($this->user->reminders()->sole())->days_of_week->toBeNull()->fire_on->toDateString()->toBe('2027-01-01');
    });

    it('attaches a goal-scoped reminder to the owner\'s goal', function () {
        $goal = Goal::factory()->for($this->user)->create();

        $this->actingAs($this->user)->post(route('reminders.store'), reminderInput(['kind' => 'pace_summary', 'goal_id' => $goal->id]));

        expect($this->user->reminders()->sole()->goal_id)->toBe($goal->id);
    });

    it('rejects invalid input with an error per field', function (array $overrides, string $field) {
        $this->actingAs($this->user)
            ->post(route('reminders.store'), reminderInput($overrides))
            ->assertSessionHasErrors($field);

        expect(Reminder::query()->count())->toBe(0);
    })->with([
        'no label' => [['label' => ''], 'label'],
        'a bad time' => [['time' => '25:00'], 'time'],
        'unknown kind' => [['kind' => 'nag'], 'kind'],
        'no channel' => [['channels' => []], 'channels'],
        'unknown channel' => [['channels' => ['sms']], 'channels.0'],
        'a weekday out of range' => [['days_of_week' => [8]], 'days_of_week.0'],
        'neither weekdays nor a date' => [['days_of_week' => null], 'days_of_week'],
        'both weekdays and a date' => [['fire_on' => '2027-01-01'], 'days_of_week'],
        'a goal kind without a goal' => [['kind' => 'deadline_alert'], 'goal_id'],
    ]);

    it('rejects another user\'s goal', function () {
        $foreign = Goal::factory()->for(User::factory()->create())->create();

        $this->actingAs($this->user)
            ->post(route('reminders.store'), reminderInput(['goal_id' => $foreign->id]))
            ->assertSessionHasErrors('goal_id');
    });

    it('redirects guests to the sign in page', function () {
        $this->post(route('reminders.store'), reminderInput())->assertRedirect(route('login'));
    });
});

describe('editing a reminder', function () {
    it('replaces every field', function () {
        $reminder = Reminder::factory()->for($this->user)->create(['label' => 'Old']);

        $this->actingAs($this->user)
            ->patch(route('reminders.update', $reminder), reminderInput(['label' => 'New', 'time' => '21:00', 'is_active' => false]))
            ->assertRedirect();

        expect($reminder->fresh())->label->toBe('New')->is_active->toBeFalse()->time->toStartWith('21:00');
    });

    it('turns a recurring reminder into a once-only one', function () {
        $reminder = Reminder::factory()->for($this->user)->create();

        $this->actingAs($this->user)->patch(route('reminders.update', $reminder), reminderInput(['days_of_week' => null, 'fire_on' => '2026-12-24']));

        expect($reminder->fresh())->days_of_week->toBeNull()->fire_on->toDateString()->toBe('2026-12-24');
    });

    it('does not let another user edit the reminder', function () {
        $reminder = Reminder::factory()->for($this->user)->create(['label' => 'Mine']);

        $this->actingAs(User::factory()->create())->patch(route('reminders.update', $reminder), reminderInput())->assertForbidden();

        expect($reminder->fresh()->label)->toBe('Mine');
    });
});

describe('deleting a reminder', function () {
    it('removes it', function () {
        $reminder = Reminder::factory()->for($this->user)->create();

        $this->actingAs($this->user)->delete(route('reminders.destroy', $reminder))->assertRedirect();

        expect(Reminder::query()->count())->toBe(0);
    });

    it('does not let another user delete it', function () {
        $reminder = Reminder::factory()->for($this->user)->create();

        $this->actingAs(User::factory()->create())->delete(route('reminders.destroy', $reminder))->assertForbidden();

        expect(Reminder::query()->count())->toBe(1);
    });
});

describe('what the sheet fills in', function () {
    it('gives the dashboard reminders their raw values', function () {
        Reminder::factory()->for($this->user)->create(['kind' => ReminderKind::EveningCheckin, 'label' => 'Evening', 'time' => '21:30:00', 'days_of_week' => [1, 2], 'channels' => [ReminderChannel::Telegram, ReminderChannel::Email]]);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('reminders.0.form', [
                    'id' => Reminder::query()->sole()->id,
                    'kind' => 'evening_checkin',
                    'label' => 'Evening',
                    'time' => '21:30',
                    'daysOfWeek' => [1, 2],
                    'fireOn' => null,
                    'goalId' => null,
                    'channels' => ['telegram', 'email'],
                    'isActive' => true,
                ]));
    });

    it('gives the goal page its reminders with the same raw values', function () {
        $goal = Goal::factory()->for($this->user)->create();
        Reminder::factory()->forGoal($goal)->once(day('2027-01-01'))->create();

        $this->actingAs($this->user)
            ->get(route('goals.show', $goal))
            ->assertInertia(fn (Assert $page) => $page
                ->where('reminders.0.form.goalId', $goal->id)
                ->where('reminders.0.form.fireOn', '2027-01-01')
                ->where('reminders.0.form.daysOfWeek', null));
    });
});

it('rejects another user\'s goal when editing a reminder', function () {
    $reminder = Reminder::factory()->for($this->user)->create();
    $foreign = Goal::factory()->for(User::factory()->create())->create();

    $this->actingAs($this->user)
        ->patch(route('reminders.update', $reminder), reminderInput(['goal_id' => $foreign->id]))
        ->assertSessionHasErrors('goal_id');
});
