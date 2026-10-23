<?php

declare(strict_types=1);

use App\Data\DayScore;
use App\Services\Scoring\ConsistencyService;
use App\Services\Scoring\ScoreService;

beforeEach(function () {
    $this->service = new ConsistencyService(new ScoreService);
});

describe('heat level', function () {
    it('grades a day against the target', function (int $points, int $level) {
        expect((new DayScore(day('2026-10-23'), $points, 14))->heatLevel())->toBe($level);
    })->with([
        'no points' => [0, 0],
        'under half' => [6, 1],
        'exactly half' => [7, 2],
        'just under a good day' => [9, 2],
        'good day' => [10, 3],
        'just under target' => [13, 3],
        'target hit' => [14, 4],
    ]);
});

describe('weeks', function () {
    it('builds twelve Monday-first weeks ending with the current one', function () {
        $consistency = $this->service->weeks(collect(), day('2026-10-23'), 14);

        expect($consistency->weeks)->toHaveCount(12)
            ->and($consistency->weeks[0])->toHaveCount(7);
    });

    it('leaves days after today empty', function () {
        $consistency = $this->service->weeks(collect(), day('2026-10-23'), 14);

        expect($consistency->weeks[11])->toBe([0, 0, 0, 0, 0, null, null]);
    });

    it('places each day in its weekday column', function () {
        $completions = completionsWithPoints(['2026-10-19' => 14, '2026-10-22' => 10, '2026-10-12' => 7]);

        $consistency = $this->service->weeks($completions, day('2026-10-23'), 14);

        expect($consistency->weeks[11][0])->toBe(4)
            ->and($consistency->weeks[11][3])->toBe(3)
            ->and($consistency->weeks[10][0])->toBe(2);
    });

    it('starts eleven weeks before the current week', function () {
        $completions = completionsWithPoints(['2026-08-03' => 14, '2026-08-02' => 14]);

        $consistency = $this->service->weeks($completions, day('2026-10-23'), 14);

        expect($consistency->weeks[0][0])->toBe(4)
            ->and($consistency->goodDays)->toBe(1);
    });

    it('counts good days over the days that have happened', function () {
        $completions = completionsWithPoints(['2026-10-19' => 14, '2026-10-20' => 10, '2026-10-21' => 9]);

        $consistency = $this->service->weeks($completions, day('2026-10-23'), 14);

        expect($consistency->totalDays)->toBe(11 * 7 + 5)
            ->and($consistency->goodDays)->toBe(2)
            ->and($consistency->goodPercent)->toBe(2);
    });
});
