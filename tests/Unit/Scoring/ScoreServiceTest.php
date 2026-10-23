<?php

declare(strict_types=1);

use App\Data\DayScore;
use App\Enums\PointsTier;
use App\Services\Scoring\ScoreService;

beforeEach(function () {
    $this->service = new ScoreService;
});

describe('day score', function () {
    it('sums the points of the completions on that day only', function () {
        $completions = completionsWithPoints(['2026-10-22' => 5, '2026-10-23' => 9]);
        $completions->push(...completionsWithPoints(['2026-10-23' => 3]));

        expect($this->service->dayScore($completions, day('2026-10-23'), 14)->points)->toBe(12);
    });

    it('is zero on a day without completions', function () {
        expect($this->service->dayScore(collect(), day('2026-10-23'), 14)->points)->toBe(0);
    });

    it('rounds the good day threshold up', function (int $target, int $threshold) {
        expect((new DayScore(day('2026-10-23'), 0, $target))->goodDayThreshold())->toBe($threshold);
    })->with([
        'default target' => [14, 10],
        'exact 70%' => [10, 7],
        'twenty' => [20, 14],
        'tiny target' => [1, 1],
    ]);

    it('tiers a day against the target', function (int $points, PointsTier $tier) {
        expect((new DayScore(day('2026-10-23'), $points, 14))->tier())->toBe($tier);
    })->with([
        'nothing' => [0, PointsTier::Low],
        'one short of good' => [9, PointsTier::Low],
        'good day' => [10, PointsTier::Good],
        'one short of target' => [13, PointsTier::Good],
        'target hit' => [14, PointsTier::Target],
        'over target' => [18, PointsTier::Target],
    ]);
});

describe('week score', function () {
    it('compares Monday to today with the same span last week', function () {
        $completions = completionsWithPoints([
            '2026-10-12' => 10, '2026-10-13' => 12, '2026-10-14' => 5, '2026-10-15' => 8, '2026-10-16' => 7, '2026-10-17' => 20,
            '2026-10-19' => 11, '2026-10-20' => 14, '2026-10-21' => 12, '2026-10-22' => 11, '2026-10-23' => 9,
        ]);

        $week = $this->service->weekScore($completions, day('2026-10-23'));

        expect($week->points)->toBe(57)
            ->and($week->previousPoints)->toBe(42)
            ->and($week->changePercent)->toBe(36);
    });

    it('has no percentage when last week had no points', function () {
        $week = $this->service->weekScore(completionsWithPoints(['2026-10-19' => 5]), day('2026-10-21'));

        expect($week->points)->toBe(5)
            ->and($week->changePercent)->toBeNull();
    });

    it('covers a single day on a Monday', function () {
        $completions = completionsWithPoints(['2026-10-12' => 10, '2026-10-13' => 99, '2026-10-19' => 5]);

        $week = $this->service->weekScore($completions, day('2026-10-19'));

        expect($week->previousPoints)->toBe(10)
            ->and($week->changePercent)->toBe(-50);
    });
});

describe('series', function () {
    it('lists the days oldest first and ending today, empty days included', function () {
        $series = $this->service->series(completionsWithPoints(['2026-10-23' => 9, '2026-10-01' => 14]), day('2026-10-23'), 14);

        expect($series->days)->toHaveCount(30)
            ->and($series->days[0]->date->toDateString())->toBe('2026-09-24')
            ->and($series->days[29]->date->toDateString())->toBe('2026-10-23')
            ->and($series->days[29]->points)->toBe(9)
            ->and($series->days[7]->points)->toBe(14);
    });

    it('summarises average, best day and days at target', function () {
        $series = $this->service->series(
            completionsWithPoints(['2026-10-23' => 9, '2026-10-22' => 14, '2026-10-21' => 18]),
            day('2026-10-23'),
            14,
        );

        expect($series->average)->toBe(1.4)
            ->and($series->bestDay)->toBe(18)
            ->and($series->daysAtTarget)->toBe(2);
    });
});
