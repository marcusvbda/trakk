<?php

declare(strict_types=1);

use App\Enums\GoalColor;
use App\Enums\GoalType;
use App\Enums\TaskStatus;
use App\Models\Goal;
use App\Models\Task;
use App\Services\Dashboard\AgendaBuilder;
use Illuminate\Support\Collection;

function agendaGoal(string $due = '2027-01-15'): Goal
{
    return new Goal(['title' => 'Nomad visa · Spain', 'type' => GoalType::Project, 'color' => GoalColor::Amber, 'start_date' => day('2026-07-01'), 'due_date' => day($due)]);
}

/**
 * @param  array<string, mixed>  $attributes
 */
function agendaTask(Goal $goal, string $title, array $attributes): Task
{
    static $nextId = 1;

    return (new Task(['title' => $title, 'status' => TaskStatus::Todo, ...$attributes]))->forceFill(['id' => $nextId++])->setRelation('goal', $goal);
}

describe('overdue', function () {
    it('lists open one-off tasks past their date, the latest first', function () {
        $goal = agendaGoal();
        $tasks = Collection::make([
            agendaTask($goal, 'one day late', ['due_date' => day('2026-10-22')]),
            agendaTask($goal, 'five days late', ['due_date' => day('2026-10-18')]),
            agendaTask($goal, 'due today', ['due_date' => day('2026-10-23')]),
            agendaTask($goal, 'done', ['due_date' => day('2026-10-10'), 'status' => TaskStatus::Done]),
            agendaTask($goal, 'recurring', ['days_of_week' => [1]]),
        ]);

        $overdue = app(AgendaBuilder::class)->overdue($tasks, day('2026-10-23'));

        expect(array_map(fn ($item): string => $item->title, $overdue))->toBe(['five days late', 'one day late'])
            ->and($overdue[0]->daysLate)->toBe(5)
            ->and($overdue[0]->dueLabel)->toBe('18 oct')
            ->and($overdue[0]->goalName)->toBe('nomad visa');
    });
});

describe('upcoming', function () {
    it('lists tasks and goal deadlines in the next seven days, in date order', function () {
        $goal = agendaGoal('2026-10-28');
        $tasks = Collection::make([
            agendaTask($goal, 'due today', ['due_date' => day('2026-10-23')]),
            agendaTask($goal, 'in two days', ['due_date' => day('2026-10-25')]),
            agendaTask($goal, 'in a week', ['due_date' => day('2026-10-30')]),
            agendaTask($goal, 'beyond the week', ['due_date' => day('2026-10-31')]),
        ]);

        $upcoming = app(AgendaBuilder::class)->upcoming(Collection::make([$goal]), $tasks, day('2026-10-23'));

        expect(array_map(fn ($item): string => $item->title, $upcoming))->toBe(['in two days', 'Nomad visa · Spain', 'in a week'])
            ->and($upcoming[0]->dayLabel)->toBe('SUN 25')
            ->and($upcoming[1]->goalName)->toBe('goal deadline');
    });
});

describe('hints', function () {
    it('names the next day with something scheduled and the next deadline', function () {
        $goal = agendaGoal('2027-01-15');
        $tasks = Collection::make([
            agendaTask($goal, 'later', ['due_date' => day('2026-11-20')]),
            agendaTask($goal, 'soon', ['due_date' => day('2026-11-02')]),
            agendaTask($goal, 'weekly', ['days_of_week' => [3]]),
        ]);

        $hints = app(AgendaBuilder::class)->hints(Collection::make([$goal]), $tasks, day('2026-10-23'));

        expect($hints->nextActionDay)->toBe('next: wed 28')
            ->and($hints->nextDeadline->title)->toBe('soon')
            ->and($hints->nextDeadline->dateLabel)->toBe('02 nov');
    });

    it('has no hints without goals or tasks', function () {
        $hints = app(AgendaBuilder::class)->hints(Collection::make(), Collection::make(), day('2026-10-23'));

        expect($hints->nextActionDay)->toBeNull()
            ->and($hints->nextDeadline)->toBeNull();
    });
});
