<?php

declare(strict_types=1);

use App\Models\Reminder;
use App\Models\User;
use App\Services\Dashboard\StatusLineService;
use Illuminate\Support\Collection;

function nextReminderAt(Collection $reminders, string $now): ?string
{
    test()->travelTo(day($now));
    $user = User::factory()->make(['timezone' => 'UTC']);

    return app(StatusLineService::class)->for($user, $reminders, day($now)->startOfDay())->nextReminderAt;
}

describe('next reminder', function () {
    beforeEach(function () {
        $this->reminders = Collection::make([
            Reminder::factory()->make(['time' => '07:30:00']),
            Reminder::factory()->make(['time' => '21:30:00']),
            Reminder::factory()->make(['time' => '18:00:00']),
            Reminder::factory()->make(['time' => '19:00:00', 'is_active' => false]),
        ]);
    });

    it('is the next active one still to come today', function (string $now, string $expected) {
        expect(nextReminderAt($this->reminders, $now))->toBe($expected);
    })->with([
        'early morning' => ['2026-10-23 06:00', '07:30'],
        'afternoon' => ['2026-10-23 13:00', '18:00'],
        'late evening' => ['2026-10-23 20:00', '21:30'],
    ]);

    it('falls back to the first one tomorrow once today\'s are over', function () {
        expect(nextReminderAt($this->reminders, '2026-10-23 22:00'))->toBe('07:30');
    });

    it('finds a weekly reminder days ahead and names its weekday', function () {
        $sundayOnly = Collection::make([Reminder::factory()->make(['time' => '10:00:00', 'days_of_week' => [7]])]);

        expect(nextReminderAt($sundayOnly, '2026-10-23 08:00'))->toBe('sun 10:00')
            ->and(nextReminderAt($sundayOnly, '2026-10-24 08:00'))->toBe('10:00')
            ->and(nextReminderAt($sundayOnly, '2026-10-25 11:00'))->toBe('sun 10:00');
    });

    it('ignores a once reminder that is more than a week away', function () {
        $farAway = Collection::make([Reminder::factory()->once(day('2026-12-01'))->make(['time' => '10:00:00'])]);

        expect(nextReminderAt($farAway, '2026-10-23 08:00'))->toBeNull();
    });

    it('is empty when there are no active reminders', function () {
        expect(nextReminderAt(Collection::make(), '2026-10-23 08:00'))->toBeNull();
    });
});

describe('telegram', function () {
    it('is active only with a token, a linked chat and no failed send', function (?string $token, ?string $chat, ?string $error, bool $expected) {
        config(['services.telegram.bot_token' => $token]);
        $user = User::factory()->make(['telegram_chat_id' => $chat]);
        $reminders = Collection::make([Reminder::factory()->make(['last_error' => $error])]);

        $status = app(StatusLineService::class)->for($user, $reminders, day('2026-10-23'));

        expect($status->isTelegramActive)->toBe($expected)
            ->and($status->isTelegramLinked)->toBe($chat !== null);
    })->with([
        'all set' => ['token', '123', null, true],
        'no token' => [null, '123', null, false],
        'not linked' => ['token', null, null, false],
        'last send failed' => ['token', '123', 'chat not found', false],
    ]);
});
