<?php

declare(strict_types=1);

use App\Enums\GoalType;
use App\Enums\TaskStatus;
use App\Models\Completion;
use App\Models\Goal;
use App\Models\Task;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Collection;

/**
 * In-memory models for calculator tests: nothing touches the database.
 */
function day(string $date): CarbonImmutable
{
    return CarbonImmutable::parse($date);
}

/**
 * @param  array<string, int>  $pointsByDate
 * @return Collection<int, Completion>
 */
function completionsWithPoints(array $pointsByDate): Collection
{
    return collect($pointsByDate)->map(fn (int $points, string $date): Completion => new Completion([
        'completed_on' => $date,
        'points' => $points,
    ]))->values();
}

function projectGoal(string $start, string $due, int $done, int $total): Goal
{
    $goal = new Goal([
        'type' => GoalType::Project,
        'start_date' => $start,
        'due_date' => $due,
        'target_per_week' => null,
    ]);

    $tasks = collect(range(1, max($total, 1)))->take($total)->map(fn (int $index): Task => new Task([
        'status' => $index <= $done ? TaskStatus::Done : TaskStatus::Todo,
        'points' => 1,
    ]));

    return $goal->setRelation('tasks', $tasks->values());
}

/**
 * A project whose done tasks were each completed on one of `$doneDates`, with the rest still to do.
 *
 * @param  list<string>  $doneDates
 */
function projectGoalDoneOn(string $start, string $due, int $total, array $doneDates): Goal
{
    $goal = new Goal(['type' => GoalType::Project, 'start_date' => $start, 'due_date' => $due, 'target_per_week' => null]);

    $tasks = collect(range(1, max($total, 1)))->take($total)->map(function (int $index) use ($doneDates): Task {
        $doneOn = $doneDates[$index - 1] ?? null;
        $task = new Task(['status' => $doneOn === null ? TaskStatus::Todo : TaskStatus::Done, 'points' => 1]);

        return $task->setRelation('completions', collect($doneOn === null ? [] : [new Completion(['completed_on' => $doneOn, 'points' => 1])]));
    });

    return $goal->setRelation('tasks', $tasks->values());
}

/**
 * @param  list<string>  $completionDates  one completion per date on the goal's single task
 */
function habitGoal(string $start, string $due, int $perWeek, array $completionDates, int $tasks = 1): Goal
{
    $goal = new Goal([
        'type' => GoalType::Habit,
        'start_date' => $start,
        'due_date' => $due,
        'target_per_week' => $perWeek,
    ]);

    $completions = collect($completionDates)->map(fn (string $date): Completion => new Completion([
        'completed_on' => $date,
        'points' => 1,
    ]));

    $taskModels = collect(range(1, $tasks))->map(fn (int $index): Task => (new Task(['status' => TaskStatus::Todo, 'days_of_week' => [1, 2, 3, 4, 5, 6, 7]]))
        ->setRelation('completions', $index === 1 ? $completions : collect()));

    return $goal->setRelation('tasks', $taskModels);
}

/**
 * Seeds the owner plus the demo data as of `$date` and returns the owner.
 */
function seedDemoOwner(string $date = '2026-10-23'): User
{
    config(['trakk.owner.email' => 'owner@example.com', 'trakk.owner.password' => 'secret-pass-123']);

    test()->travelTo(day($date)->setTime(12, 0));
    test()->seed(DatabaseSeeder::class);

    return User::query()->sole();
}

/**
 * @param  list<int>  $overdueByDays
 */
function goalWithOverdue(string $title, array $overdueByDays, int $doneTasks = 0): Goal
{
    $goal = new Goal([
        'title' => $title,
        'type' => GoalType::Project,
        'start_date' => day('2026-07-01'),
        'due_date' => day('2027-01-15'),
    ]);

    $tasks = collect($overdueByDays)
        ->map(fn (int $days): Task => new Task(['status' => TaskStatus::Todo, 'due_date' => day('2026-10-23')->subDays($days)]))
        ->concat(array_map(fn (): Task => new Task(['status' => TaskStatus::Done]), array_fill(0, $doneTasks, null)));

    return $goal->setRelation('tasks', $tasks->values());
}
