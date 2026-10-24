<?php

declare(strict_types=1);

use App\Enums\GoalStatus;
use App\Enums\TaskStatus;
use App\Models\Completion;
use App\Models\Goal;
use App\Models\Task;
use App\Models\User;
use App\Services\Goals\GoalProgressService;
use App\Services\Scoring\ScoreService;
use App\Services\Scoring\StreakService;
use App\Support\Clock;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;

beforeEach(function () {
    config(['trakk.owner.email' => 'owner@example.com', 'trakk.owner.password' => 'secret-pass-123']);
});

function seedDemoOn(string $date): array
{
    test()->travelTo(day($date)->setTime(12, 0));
    test()->seed(DatabaseSeeder::class);

    $owner = User::query()->sole();

    return [$owner, app(Clock::class)->today($owner)];
}

describe('demo data relative to today', function () {
    it('lists the prototype eight actions for today and a day score of 9 whichever day it runs', function (string $date) {
        [$owner, $today] = seedDemoOn($date);

        $tasks = Task::query()->whereIn('goal_id', $owner->goals()->select('id'))->get();
        $todayList = $tasks->filter(fn (Task $task): bool => $task->isScheduledOn($today)
            || ($task->due_date?->equalTo($today) && ! $task->isRecurring())
            || (! $task->isRecurring() && $task->status === TaskStatus::Doing && $task->due_date === null));
        $score = app(ScoreService::class)->dayScore(Completion::query()->get(), $today, $owner->daily_points_target);

        expect($todayList)->toHaveCount(8)
            ->and($score->points)->toBe(9);
    })->with(['a friday' => '2026-10-23', 'a tuesday' => '2026-11-10', 'a monday' => '2027-02-01']);

    it('has a streak of 12 days with a record of 21 and an unfinished today', function (string $date) {
        [$owner, $today] = seedDemoOn($date);

        $streak = app(StreakService::class)->streak($owner->completions()->get(), $today, $owner->daily_points_target);

        expect($streak->current)->toBe(12)
            ->and($streak->record)->toBe(21);
    })->with(['a friday' => '2026-10-23', 'a tuesday' => '2026-11-10', 'a monday' => '2027-02-01']);

    it('puts four goals on pace and two off pace, with the nomad visa at 9 of 20', function () {
        [$owner, $today] = seedDemoOn('2026-10-23');
        $service = app(GoalProgressService::class);

        $goals = $owner->goals()->active()->with('tasks.completions')->get();
        $paces = $goals->mapWithKeys(fn (Goal $goal): array => [$goal->slug => $service->pace($goal, $today)]);
        $visa = $goals->firstWhere('slug', 'nomad-visa');

        expect($goals)->toHaveCount(6)
            ->and($paces->filter(fn ($pace): bool => $pace->status->isOnPace())->keys()->sort()->values()->all())->toBe(['diet', 'ielts', 'moving-fund', 'training'])
            ->and($paces['nomad-visa']->progress)->toBe(45)
            ->and($paces['nomad-visa']->delta)->toBe(-13)
            ->and($visa->tasks)->toHaveCount(20)
            ->and($visa->tasks->countBy(fn (Task $task) => $task->status->value)->all())->toEqual(['todo' => 9, 'doing' => 2, 'done' => 9]);
    });

    it('has four overdue tasks, two of them from the nomad visa', function () {
        [$owner, $today] = seedDemoOn('2026-10-23');

        $overdue = Task::query()->overdue($today)->whereIn('goal_id', $owner->goals()->select('id'))->with('goal')->get();

        expect($overdue)->toHaveCount(4)
            ->and($overdue->where('goal.slug', 'nomad-visa'))->toHaveCount(2);
    });
});

describe('seeding', function () {
    it('seeds the demo data once and the default reminders with it', function () {
        [$owner] = seedDemoOn('2026-10-23');

        $this->seed(DemoSeeder::class);

        expect($owner->goals()->count())->toBe(6)
            ->and($owner->goals()->where('status', GoalStatus::Active)->count())->toBe(6)
            ->and($owner->reminders()->whereNull('goal_id')->count())->toBe(5)
            ->and($owner->reminders()->whereNotNull('goal_id')->count())->toBe(3);
    });

    it('seeds no demo data in production, only the owner and the default reminders', function () {
        $this->app['env'] = 'production';

        app(DatabaseSeeder::class)->setContainer($this->app)->__invoke();

        $owner = User::query()->sole();
        expect($owner->goals()->count())->toBe(0)
            ->and($owner->reminders()->count())->toBe(5);
    });
});
