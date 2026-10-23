<?php

declare(strict_types=1);

use App\Services\Scoring\ScoreService;
use App\Services\Scoring\StreakService;

beforeEach(function () {
    $this->service = new StreakService(new ScoreService);
});

/**
 * @return array<string, int> 12 for each of the given days, a good day with the default target of 14
 */
function goodDays(string ...$dates): array
{
    return array_fill_keys($dates, 12);
}

describe('streak', function () {
    it('counts consecutive good days ending today', function () {
        $completions = completionsWithPoints(goodDays('2026-10-21', '2026-10-22', '2026-10-23'));

        expect($this->service->streak($completions, day('2026-10-23'), 14)->current)->toBe(3);
    });

    it('keeps counting from yesterday while today is not a good day yet', function () {
        $completions = completionsWithPoints(goodDays('2026-10-21', '2026-10-22') + ['2026-10-23' => 9]);

        expect($this->service->streak($completions, day('2026-10-23'), 14)->current)->toBe(2);
    });

    it('is zero when neither today nor yesterday is a good day', function () {
        $completions = completionsWithPoints(goodDays('2026-10-19', '2026-10-20', '2026-10-21'));

        expect($this->service->streak($completions, day('2026-10-23'), 14)->current)->toBe(0);
    });

    it('breaks on a day short of a good day', function () {
        $completions = completionsWithPoints(goodDays('2026-10-20', '2026-10-22', '2026-10-23') + ['2026-10-21' => 9]);

        expect($this->service->streak($completions, day('2026-10-23'), 14)->current)->toBe(2);
    });

    it('adds up points of several completions in a day', function () {
        $completions = completionsWithPoints(['2026-10-23' => 6]);
        $completions->push(...completionsWithPoints(['2026-10-23' => 4]));

        expect($this->service->streak($completions, day('2026-10-23'), 14)->current)->toBe(1);
    });

    it('keeps the longest run as the record', function () {
        $completions = completionsWithPoints(goodDays(
            '2026-09-01', '2026-09-02', '2026-09-03', '2026-09-04',
            '2026-10-22', '2026-10-23',
        ));

        $streak = $this->service->streak($completions, day('2026-10-23'), 14);

        expect($streak->current)->toBe(2)
            ->and($streak->record)->toBe(4);
    });

    it('is zero for a user without completions', function () {
        $streak = $this->service->streak(collect(), day('2026-10-23'), 14);

        expect($streak->current)->toBe(0)
            ->and($streak->record)->toBe(0);
    });
});
