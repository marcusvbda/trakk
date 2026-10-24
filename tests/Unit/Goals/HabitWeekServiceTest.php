<?php

declare(strict_types=1);

use App\Models\Completion;
use App\Models\Goal;
use App\Models\Task;
use App\Models\User;
use App\Services\Goals\HabitWeekService;

/**
 * @param  list<list<int>>  $actionDays  weekdays of each recurring action
 */
function habitCreatedOn(string $createdOn, array $actionDays, ?string $actionsCreatedOn = null): Goal
{
    $goal = new Goal;
    $goal->created_at = day($createdOn);
    $goal->setRelation('user', new User(['timezone' => 'UTC']));
    $goal->setRelation('tasks', collect($actionDays)->map(function (array $days) use ($createdOn, $actionsCreatedOn): Task {
        $task = new Task(['days_of_week' => $days]);
        $task->created_at = day($actionsCreatedOn ?? $createdOn);
        $task->setRelation('completions', collect());

        return $task;
    }));

    return $goal;
}

describe('week numbers', function () {
    it('starts week 1 in the week the goal was created and rolls on Monday', function (string $createdOn, string $at, int $week) {
        expect((new HabitWeekService)->weekNumber(habitCreatedOn($createdOn, [[1]]), day($at)))->toBe($week);
    })->with([
        'mid-week creation, same week' => ['2026-10-21', '2026-10-24', 1],
        'mid-week creation, next Monday' => ['2026-10-21', '2026-10-26', 2],
        'created on a Sunday, week 2 starts the next day' => ['2026-10-25', '2026-10-26', 2],
        'two weeks later' => ['2026-10-21', '2026-11-03', 3],
    ]);
});

describe('which days can be checked', function () {
    it('allows scheduled days from Monday up to today only', function (string $on, bool $allowed) {
        $task = habitCreatedOn('2026-10-19', [[1, 2, 3, 4, 5]])->tasks->first();

        expect((new HabitWeekService)->canCheck($task, day($on), day('2026-10-21')))->toBe($allowed);
    })->with([
        'Monday of this week' => ['2026-10-19', true],
        'today' => ['2026-10-21', true],
        'tomorrow' => ['2026-10-22', false],
        'a Friday of the previous week' => ['2026-10-16', false],
        'a day the action is not scheduled' => ['2026-10-24', false],
    ]);
});

describe('closed weeks', function () {
    it('lists the latest week first and counts only checks on scheduled days', function () {
        $goal = habitCreatedOn('2026-10-21', [[1, 2, 3, 4, 5]]);
        $goal->tasks->first()->setRelation('completions', collect([
            new Completion(['completed_on' => '2026-10-20']),
            new Completion(['completed_on' => '2026-10-24']),
        ]));

        $weeks = (new HabitWeekService)->closedWeeks($goal, day('2026-11-03'));

        expect(array_map(fn ($week) => [$week->number, $week->doneCount, $week->totalCount], $weeks))
            ->toBe([[2, 0, 5], [1, 1, 5]]);
    });

    it('leaves an action out of the weeks that ended before it existed', function () {
        $goal = habitCreatedOn('2026-10-21', [[1, 2, 3, 4, 5]]);
        $late = new Task(['days_of_week' => [6]]);
        $late->created_at = day('2026-10-29');
        $late->setRelation('completions', collect());
        $goal->tasks->push($late);

        $weeks = (new HabitWeekService)->closedWeeks($goal, day('2026-11-03'));

        expect(array_map(fn ($week) => $week->totalCount, $weeks))->toBe([6, 5]);
    });

    it('keeps at most twelve weeks', function () {
        $goal = habitCreatedOn('2026-01-05', [[1]]);

        expect((new HabitWeekService)->closedWeeks($goal, day('2026-10-21')))->toHaveCount(12);
    });
});
