<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\Clock;

describe('today', function () {
    it('is the calendar date in the user timezone', function (string $instant, string $timezone, string $expected) {
        $this->travelTo(day($instant));
        $user = new User(['timezone' => $timezone]);

        expect((new Clock)->today($user)->toDateString())->toBe($expected);
    })->with([
        'dublin is ahead of utc in summer time' => ['2026-10-23 23:30:00', 'Europe/Dublin', '2026-10-24'],
        'dublin on the same day' => ['2026-10-23 12:00:00', 'Europe/Dublin', '2026-10-23'],
        'new york is behind utc' => ['2026-10-23 02:00:00', 'America/New_York', '2026-10-22'],
        'utc itself' => ['2026-10-23 23:59:59', 'UTC', '2026-10-23'],
    ]);

    it('is midnight in the app timezone so it subtracts cleanly from date columns', function () {
        $this->travelTo(day('2026-10-23 15:45:00'));
        $user = new User(['timezone' => 'Europe/Dublin']);

        $today = (new Clock)->today($user);

        expect($today->format('H:i:s'))->toBe('00:00:00')
            ->and($today->diffInDays(day('2026-10-30')))->toBe(7.0);
    });
});

describe('now for a user', function () {
    it('keeps the instant and shows it on the user clock', function () {
        $this->travelTo(day('2026-10-23 12:00:00'));
        $user = new User(['timezone' => 'Europe/Dublin']);

        expect((new Clock)->nowFor($user)->format('H:i'))->toBe('13:00');
    });
});
