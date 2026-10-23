<?php

declare(strict_types=1);

use App\Data\Dashboard\KpiData;
use App\Data\DayScore;
use App\Data\OnTimeRate;
use App\Data\Streak;
use App\Data\WeekScore;
use App\Enums\Tone;
use App\Services\Dashboard\KpiService;
use Illuminate\Support\Collection;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, KpiData>
 */
function kpisFor(array $overrides = []): array
{
    $today = day('2026-10-23');
    $inputs = [
        'day' => new DayScore($today, 9, 14),
        'streak' => new Streak(12, 21),
        'week' => new WeekScore(56, 52, 8),
        'goals' => Collection::make(),
        'onTime' => new OnTimeRate(31, 36, 86),
        ...$overrides,
    ];

    return collect(app(KpiService::class)->build($inputs['day'], $inputs['streak'], $inputs['week'], $inputs['goals'], $inputs['onTime'], $today))
        ->keyBy('id')
        ->all();
}

describe('points today', function () {
    it('shows the share of the target and turns accent once it is hit', function (int $points, string $sub, Tone $valueTone) {
        $kpi = kpisFor(['day' => new DayScore(day('2026-10-23'), $points, 14)])['points-today'];

        expect($kpi->value)->toBe((string) $points)
            ->and($kpi->suffix)->toBe('/14')
            ->and($kpi->sub)->toBe($sub)
            ->and($kpi->valueTone)->toBe($valueTone);
    })->with([
        'nothing yet' => [0, 'first check of the day', Tone::Default],
        'under the target' => [9, '64% of daily target', Tone::Default],
        'on the target' => [14, 'daily target hit', Tone::Accent],
        'over the target' => [20, 'daily target hit', Tone::Accent],
    ]);

    it('caps the bar at 100%', function () {
        expect(kpisFor(['day' => new DayScore(day('2026-10-23'), 28, 14)])['points-today']->progress)->toBe(100);
    });
});

describe('streak', function () {
    it('compares the current run with the record', function () {
        $kpi = kpisFor()['streak'];

        expect($kpi->value)->toBe('12')
            ->and($kpi->suffix)->toBe(' days')
            ->and($kpi->progress)->toBe(57)
            ->and($kpi->sub)->toBe('record · 21 days');
    });

    it('keeps the record line when the run is broken and invites a new one when there never was any', function (int $record, string $sub) {
        $kpi = kpisFor(['streak' => new Streak(0, $record)])['streak'];

        expect($kpi->value)->toBe('0')
            ->and($kpi->sub)->toBe($sub);
    })->with([
        'a record exists' => [7, 'record · 7 days'],
        'no record' => [0, 'start one today'],
    ]);

    it('uses the singular for one day', function () {
        expect(kpisFor(['streak' => new Streak(1, 1)])['streak']->suffix)->toBe(' day');
    });
});

describe('this week', function () {
    it('signs the change and colors it by direction', function (?int $change, string $sub, Tone $tone) {
        $kpi = kpisFor(['week' => new WeekScore(56, 52, $change)])['this-week'];

        expect($kpi->sub)->toBe($sub)
            ->and($kpi->subTone)->toBe($tone);
    })->with([
        'up' => [8, '+8% vs last week', Tone::Accent],
        'flat' => [0, '+0% vs last week', Tone::Accent],
        'down' => [-12, '−12% vs last week', Tone::Danger],
        'no data last week' => [null, 'no data last week', Tone::Muted],
    ]);
});

describe('goals on pace', function () {
    it('has nothing to count without active goals', function () {
        $kpi = kpisFor()['goals-on-pace'];

        expect($kpi->value)->toBe('—')
            ->and($kpi->sub)->toBe('no active goals')
            ->and($kpi->progress)->toBeNull();
    });

    it('counts goals with a delta of zero or more as on pace', function () {
        $goals = Collection::make([
            goalWithOverdue('Ahead', [], doneTasks: 10),
            goalWithOverdue('Behind', [2]),
        ]);

        $kpi = kpisFor(['goals' => $goals])['goals-on-pace'];

        expect($kpi->value)->toBe('1')
            ->and($kpi->suffix)->toBe('/2')
            ->and($kpi->sub)->toBe('1 off pace')
            ->and($kpi->subTone)->toBe(Tone::Warning);
    });
});

describe('overdue', function () {
    it('is all clear at zero', function () {
        $kpi = kpisFor()['overdue'];

        expect($kpi->value)->toBe('0')
            ->and($kpi->valueTone)->toBe(Tone::Accent)
            ->and($kpi->sub)->toBe('all clear');
    });

    it('totals the overdue tasks and names the most behind goal with the share it owns', function () {
        $goals = Collection::make([
            goalWithOverdue('Read 12 books', [5], doneTasks: 9),
            goalWithOverdue('Nomad visa · Spain', [3, 1]),
        ]);

        $kpi = kpisFor(['goals' => $goals])['overdue'];

        expect($kpi->value)->toBe('3')
            ->and($kpi->valueTone)->toBe(Tone::Danger)
            ->and($kpi->sub)->toBe('2 from nomad visa');
    });
});

describe('on time', function () {
    it('shows the delivered share', function () {
        $kpi = kpisFor()['on-time'];

        expect($kpi->value)->toBe('86')
            ->and($kpi->suffix)->toBe('%')
            ->and($kpi->sub)->toBe('31 of 36 delivered');
    });

    it('has no percentage without deadlines in the window', function () {
        $kpi = kpisFor(['onTime' => new OnTimeRate(0, 0, null)])['on-time'];

        expect($kpi->value)->toBe('—')
            ->and($kpi->sub)->toBe('no deadlines in 30d')
            ->and($kpi->progress)->toBeNull();
    });
});
