<?php

declare(strict_types=1);

use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\Reminder;
use App\Models\User;
use App\Services\Reminders\ReminderService;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->user = User::factory()->create(['timezone' => 'Europe/Dublin']);
    $this->service = app(ReminderService::class);
});

/** Dublin is UTC+1 on 2026-10-23, so 07:30 there is 06:30 UTC. */
function atUtc(string $time): CarbonImmutable
{
    return CarbonImmutable::parse("2026-10-23 {$time}", 'UTC');
}

describe('when a reminder is due', function () {
    it('is due from its slot until ten minutes later, in the owner\'s timezone', function (string $nowUtc, bool $isDue) {
        Reminder::factory()->for($this->user)->onDays([5])->create(['time' => '07:30:00']);

        expect($this->service->due(atUtc($nowUtc)))->toHaveCount($isDue ? 1 : 0);
    })->with([
        'a minute before' => ['06:29:30', false],
        'on the minute' => ['06:30:00', true],
        'within the minute' => ['06:30:45', true],
        'five minutes late' => ['06:35:00', true],
        'ten minutes late' => ['06:40:59', true],
        'eleven minutes late' => ['06:41:00', false],
        'the UTC time of the slot is not the local one' => ['07:30:00', false],
    ]);

    it('follows the owner\'s timezone', function () {
        $saoPaulo = User::factory()->create(['timezone' => 'America/Sao_Paulo']);
        Reminder::factory()->for($saoPaulo)->onDays([5])->create(['time' => '07:30:00']);

        expect($this->service->due(atUtc('10:30:00')))->toHaveCount(1)
            ->and($this->service->due(atUtc('06:30:00')))->toHaveCount(0);
    });

    it('is due only on its weekdays', function (array $days, bool $isDue) {
        Reminder::factory()->for($this->user)->onDays($days)->create(['time' => '07:30:00']);

        expect($this->service->due(atUtc('06:30:00')))->toHaveCount($isDue ? 1 : 0);
    })->with([
        'friday' => [[5], true],
        'every day' => [[1, 2, 3, 4, 5, 6, 7], true],
        'monday only' => [[1], false],
    ]);

    it('is due once on its date and never on another', function (string $date, bool $isDue) {
        Reminder::factory()->for($this->user)->once(day($date))->create(['time' => '07:30:00']);

        expect($this->service->due(atUtc('06:30:00')))->toHaveCount($isDue ? 1 : 0);
    })->with([
        'on the date' => ['2026-10-23', true],
        'the day before' => ['2026-10-24', false],
        'the day after' => ['2026-10-22', false],
    ]);

    it('skips a paused reminder', function () {
        Reminder::factory()->for($this->user)->onDays([5])->inactive()->create(['time' => '07:30:00']);

        expect($this->service->due(atUtc('06:30:00')))->toBeEmpty();
    });

    it('carries the slot the reminder is due for', function () {
        Reminder::factory()->for($this->user)->onDays([5])->create(['time' => '07:30:00']);

        $due = $this->service->due(atUtc('06:33:10'));

        expect($due[0]->slot->utc()->format('H:i'))->toBe('06:30');
    });
});

describe('when it was already handled', function () {
    it('is not due again once sent for the slot', function (string $sentAtUtc, bool $isDue) {
        Reminder::factory()->for($this->user)->onDays([5])->create(['time' => '07:30:00', 'last_sent_at' => CarbonImmutable::parse($sentAtUtc, 'UTC')]);

        expect($this->service->due(atUtc('06:32:00')))->toHaveCount($isDue ? 1 : 0);
    })->with([
        'sent for this slot' => ['2026-10-23 06:30:05', false],
        'sent a minute ago' => ['2026-10-23 06:31:00', false],
        'sent for yesterday\'s slot' => ['2026-10-22 06:30:05', true],
        'sent just before the slot' => ['2026-10-23 06:29:00', true],
    ]);

    it('is not due again once it failed for the slot, so a broken channel does not retry every minute', function () {
        $reminder = Reminder::factory()->for($this->user)->onDays([5])->create(['time' => '07:30:00']);
        $slot = atUtc('06:30:00')->setTimezone('Europe/Dublin')->setTime(7, 30);
        Activity::factory()->for($this->user)->create([
            'type' => ActivityType::ReminderFailed,
            'meta' => ['reminder_id' => $reminder->id, 'slot' => $slot->toIso8601String()],
        ]);

        expect($this->service->due(atUtc('06:32:00')))->toBeEmpty();
    });

    it('is due again for tomorrow\'s slot after a failure today', function () {
        $reminder = Reminder::factory()->for($this->user)->onDays([5, 6])->create(['time' => '07:30:00']);
        $slot = atUtc('06:30:00')->setTimezone('Europe/Dublin')->setTime(7, 30);
        Activity::factory()->for($this->user)->create([
            'type' => ActivityType::ReminderFailed,
            'meta' => ['reminder_id' => $reminder->id, 'slot' => $slot->toIso8601String()],
        ]);

        expect($this->service->due(atUtc('06:30:00')->addDay()))->toHaveCount(1);
    });
});
