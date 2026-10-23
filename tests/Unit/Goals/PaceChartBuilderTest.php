<?php

declare(strict_types=1);

use App\Services\Goals\GoalProgressService;
use App\Services\Goals\PaceChartBuilder;
use App\Support\Labels;

beforeEach(function () {
    $this->builder = new PaceChartBuilder(new GoalProgressService, new Labels);
});

describe('actual series', function () {
    it('samples five evenly spaced days from the start to today, ending on today\'s progress', function () {
        $goal = projectGoalDoneOn('2026-07-01', '2027-01-15', 4, ['2026-07-20', '2026-08-30', '2026-10-10']);

        $chart = $this->builder->build($goal, day('2026-10-23'));

        expect(array_map(fn ($point): array => [$point->day, $point->percent], $chart->actual))->toBe([
            [0.0, 0], [29.0, 25], [57.0, 25], [86.0, 50], [114.0, 75],
        ])
            ->and($chart->elapsedDays)->toBe(114)
            ->and($chart->totalDays)->toBe(198);
    });

    it('drops duplicate days when little time has elapsed', function (int $elapsed, int $points) {
        $goal = projectGoalDoneOn('2026-07-01', '2027-01-15', 4, []);

        $chart = $this->builder->build($goal, day('2026-07-01')->addDays($elapsed));

        expect($chart->actual)->toHaveCount($points);
    })->with([
        'the start day' => [0, 1],
        'one day in' => [1, 2],
        'two days in' => [2, 3],
        'a week in' => [7, 5],
    ]);
});

describe('month ticks', function () {
    it('keeps only the month starts that are far enough from each other, the start and the due date', function (string $start, string $due, array $labels) {
        $goal = projectGoalDoneOn($start, $due, 1, []);

        $chart = $this->builder->build($goal, day($start));

        expect(array_map(fn ($tick): string => $tick->label, $chart->ticks))->toBe($labels);
    })->with([
        'the prototype nomad visa' => ['2026-07-01', '2027-01-15', ['sep', 'nov']],
        'a three week goal has none' => ['2026-10-05', '2026-10-25', []],
        'a month start inside a short goal' => ['2026-10-05', '2026-11-20', ['nov']],
        'a year ticks every quarter' => ['2026-01-01', '2026-12-31', ['apr', 'jul', 'oct']],
    ]);
});

describe('empty captions', function () {
    it('says when the goal starts', function (string $today, ?string $caption) {
        $goal = projectGoalDoneOn('2026-11-01', '2027-02-01', 3, []);

        expect($this->builder->build($goal, day($today))->caption)->toBe($caption);
    })->with([
        'in four days' => ['2026-10-28', 'starts in 4 days'],
        'tomorrow' => ['2026-10-31', 'starts in 1 day'],
        'started with tasks' => ['2026-11-02', null],
    ]);

    it('asks for tasks when a started project has none', function () {
        $goal = projectGoalDoneOn('2026-07-01', '2027-01-15', 0, []);

        expect($this->builder->build($goal, day('2026-10-23'))->caption)->toBe('add tasks to measure progress');
    });

    it('labels the pace in days per week for a habit', function () {
        $goal = habitGoal('2026-01-01', '2026-03-12', 5, []);

        expect($this->builder->build($goal, day('2026-02-01'))->paceUnit)->toBe('days/wk');
    });
});
