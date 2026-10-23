<?php

declare(strict_types=1);

use App\Enums\DayState;
use App\Enums\PaceStatus;
use App\Services\Goals\GoalProgressService;

beforeEach(function () {
    $this->service = new GoalProgressService;
});

describe('expected progress', function () {
    it('is the share of the window that has elapsed', function (string $start, string $due, string $today, int $expected) {
        $goal = projectGoal($start, $due, 0, 0);

        expect($this->service->expected($goal, day($today)))->toBe($expected);
    })->with([
        'before the start' => ['2026-07-01', '2027-01-15', '2026-06-01', 0],
        'on the start day' => ['2026-07-01', '2027-01-15', '2026-07-01', 0],
        'prototype nomad visa' => ['2026-07-01', '2027-01-15', '2026-10-23', 58],
        'halfway' => ['2026-01-01', '2026-04-11', '2026-02-20', 50],
        'on the due day' => ['2026-07-01', '2027-01-15', '2027-01-15', 100],
        'after the due date' => ['2026-07-01', '2027-01-15', '2027-03-01', 100],
    ]);
});

describe('project progress', function () {
    it('is done tasks over all tasks as a whole percent', function (int $done, int $total, int $percent) {
        $goal = projectGoal('2026-07-01', '2027-01-15', $done, $total);

        expect($this->service->progress($goal))->toBe($percent);
    })->with([
        'no tasks' => [0, 0, 0],
        'nothing done' => [0, 20, 0],
        'prototype' => [9, 20, 45],
        'rounds down' => [1, 3, 33],
        'rounds up' => [2, 3, 67],
        'all done' => [20, 20, 100],
    ]);
});

describe('habit progress', function () {
    it('counts days with at least one completion against the planned days', function (int $perWeek, int $hitDays, int $percent) {
        $dates = collect(array_slice(range(0, 99), 0, $hitDays))->map(fn (int $offset): string => day('2026-01-01')->addDays($offset)->toDateString())->all();
        $goal = habitGoal('2026-01-01', '2026-03-12', $perWeek, $dates);

        expect($this->service->progress($goal))->toBe($percent);
    })->with([
        'nothing yet' => [5, 0, 0],
        'quarter of 50 planned days' => [5, 12, 24],
        'half' => [5, 25, 50],
        'capped at 100' => [5, 60, 100],
    ]);

    it('counts a day once even when several tasks were completed on it', function () {
        $goal = habitGoal('2026-01-01', '2026-03-12', 5, ['2026-01-05', '2026-01-05', '2026-01-06'], tasks: 2);

        expect($this->service->progress($goal))->toBe(4);
    });

    it('ignores completions outside the goal window', function () {
        $goal = habitGoal('2026-01-01', '2026-03-12', 5, ['2025-12-31', '2026-03-13']);

        expect($this->service->progress($goal))->toBe(0);
    });
});

describe('pace status', function () {
    it('classifies the gap between progress and expected', function (int $done, PaceStatus $status, int $delta) {
        $goal = projectGoal('2026-01-01', '2026-04-11', $done, 100);

        $pace = $this->service->pace($goal, day('2026-02-20'));

        expect($pace->expected)->toBe(50)
            ->and($pace->delta)->toBe($delta)
            ->and($pace->status)->toBe($status);
    })->with([
        'far behind' => [37, PaceStatus::Behind, -13],
        'exactly 5 behind' => [45, PaceStatus::Behind, -5],
        'just under 5 behind' => [46, PaceStatus::Watch, -4],
        'one behind' => [49, PaceStatus::Watch, -1],
        'exactly on expected' => [50, PaceStatus::OnPace, 0],
        'just under 5 ahead' => [54, PaceStatus::OnPace, 4],
        'exactly 5 ahead' => [55, PaceStatus::Ahead, 5],
    ]);

    it('counts watch as off pace and on pace or better as on pace', function () {
        expect(PaceStatus::Watch->isOnPace())->toBeFalse()
            ->and(PaceStatus::OnPace->isOnPace())->toBeTrue()
            ->and(PaceStatus::Ahead->isOnPace())->toBeTrue();
    });
});

describe('projection', function () {
    it('extends the pace so far to the due date, capped at 100', function (int $done, int $total, string $today, int $projected) {
        $goal = projectGoal('2026-07-01', '2027-01-15', $done, $total);

        expect($this->service->projection($goal, day($today)))->toBe($projected);
    })->with([
        'prototype nomad visa' => [9, 20, '2026-10-23', 78],
        'capped at 100' => [19, 20, '2026-10-23', 100],
        'nothing elapsed yet' => [0, 20, '2026-07-01', 0],
        'past the due date' => [10, 20, '2027-02-01', 50],
    ]);
});

describe('project pace', function () {
    it('gives the current and the required tasks per week', function () {
        $goal = projectGoal('2026-07-01', '2027-01-15', 9, 20);

        expect($this->service->currentPace($goal, day('2026-10-23')))->toBe(0.6)
            ->and($this->service->requiredPace($goal, day('2026-10-23')))->toBe(0.9);
    });

    it('has no current pace before the start and counts one remaining day after the due date', function () {
        $goal = projectGoal('2026-07-01', '2027-01-15', 9, 20);

        expect($this->service->currentPace($goal, day('2026-06-01')))->toBe(0.0)
            ->and($this->service->requiredPace($goal, day('2027-02-01')))->toBe(77.0);
    });
});

describe('week dots', function () {
    it('marks hit days, missed days and days still to come from Monday to Sunday', function () {
        $goal = habitGoal('2026-08-15', '2027-03-20', 5, ['2026-10-19', '2026-10-20', '2026-10-22', '2026-10-23', '2026-10-12']);

        $week = $this->service->week($goal, day('2026-10-23'));

        expect($week->days)->toBe([
            DayState::Done, DayState::Done, DayState::Missed, DayState::Done, DayState::Done, DayState::Upcoming, DayState::Upcoming,
        ])
            ->and($week->doneCount)->toBe(4)
            ->and($week->targetCount)->toBe(5);
    });

    it('keeps today upcoming until something is completed', function () {
        $goal = habitGoal('2026-08-15', '2027-03-20', 5, ['2026-10-19']);

        $week = $this->service->week($goal, day('2026-10-23'));

        expect($week->days[4])->toBe(DayState::Upcoming)
            ->and($week->days[1])->toBe(DayState::Missed);
    });
});
