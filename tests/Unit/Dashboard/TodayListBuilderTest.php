<?php

declare(strict_types=1);

use App\Enums\GoalColor;
use App\Enums\GoalType;
use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Models\Completion;
use App\Models\Goal;
use App\Models\Task;
use App\Services\Dashboard\TodayListBuilder;
use Illuminate\Support\Collection;

/**
 * @param  array<string, mixed>  $attributes
 * @param  list<string>  $completedOn
 */
function todayTask(string $title, array $attributes = [], Priority $priority = Priority::Medium, array $completedOn = []): Task
{
    $goal = new Goal(['title' => 'Goal', 'type' => GoalType::Habit, 'color' => GoalColor::Blue, 'priority' => $priority]);

    static $nextId = 1;

    return (new Task(['title' => $title, 'points' => 1, 'status' => TaskStatus::Todo, 'position' => 0, ...$attributes]))
        ->forceFill(['id' => $nextId++])
        ->setRelation('goal', $goal)
        ->setRelation('completions', collect($completedOn)->map(fn (string $date): Completion => new Completion(['completed_on' => $date, 'points' => 1])));
}

/**
 * @param  list<Task>  $tasks
 * @return list<string>
 */
function titlesOnToday(array $tasks, string $date = '2026-10-23'): array
{
    return array_map(fn ($action): string => $action->title, app(TodayListBuilder::class)->build(Collection::make($tasks), day($date)));
}

describe('build', function () {
    it('lists recurring tasks scheduled for the weekday, one-off tasks due today and one-off tasks in doing', function () {
        $tasks = [
            todayTask('daily', ['days_of_week' => [1, 2, 3, 4, 5, 6, 7]]),
            todayTask('saturdays', ['days_of_week' => [6]]),
            todayTask('due today', ['due_date' => day('2026-10-23')]),
            todayTask('due tomorrow', ['due_date' => day('2026-10-24')]),
            todayTask('in progress', ['status' => TaskStatus::Doing]),
            todayTask('in progress with a date', ['status' => TaskStatus::Doing, 'due_date' => day('2026-10-30')]),
        ];

        expect(titlesOnToday($tasks))->toEqualCanonicalizing(['daily', 'due today', 'in progress']);
    });

    it('keeps a one-off task finished today so it can be unchecked', function () {
        $finished = todayTask('finished', ['status' => TaskStatus::Done, 'due_date' => day('2026-10-20')], completedOn: ['2026-10-23']);
        $oldFinished = todayTask('finished long ago', ['status' => TaskStatus::Done, 'due_date' => day('2026-10-20')], completedOn: ['2026-10-20']);

        expect(titlesOnToday([$finished, $oldFinished]))->toBe(['finished']);
    });

    it('sorts by time, then goal priority, with tasks without a time last', function () {
        $tasks = [
            todayTask('no time low', ['days_of_week' => [5]], Priority::Low),
            todayTask('noon', ['days_of_week' => [5], 'scheduled_time' => '12:30:00']),
            todayTask('no time high', ['days_of_week' => [5]], Priority::High),
            todayTask('morning', ['days_of_week' => [5], 'scheduled_time' => '07:00:00']),
        ];

        expect(titlesOnToday($tasks))->toBe(['morning', 'noon', 'no time high', 'no time low']);
    });

    it('labels each row and marks what is done', function () {
        $tasks = [
            todayTask('timed', ['days_of_week' => [5], 'scheduled_time' => '07:00:00'], completedOn: ['2026-10-23']),
            todayTask('all day', ['days_of_week' => [5]]),
            todayTask('due', ['due_date' => day('2026-10-23')]),
            todayTask('doing', ['status' => TaskStatus::Doing]),
        ];

        $actions = collect(app(TodayListBuilder::class)->build(Collection::make($tasks), day('2026-10-23')))->keyBy('title');

        expect($actions['timed']->timeLabel)->toBe('07:00')
            ->and($actions['timed']->isDone)->toBeTrue()
            ->and($actions['all day']->timeLabel)->toBe('all day')
            ->and($actions['all day']->isDone)->toBeFalse()
            ->and($actions['due']->timeLabel)->toBe('today')
            ->and($actions['due']->isDueToday)->toBeTrue()
            ->and($actions['doing']->timeLabel)->toBe('anytime');
    });
});

describe('late recurring tasks', function () {
    /**
     * @return array<string, int>
     */
    function lateDays(Task $task): array
    {
        return collect(app(TodayListBuilder::class)->build(Collection::make([$task]), day('2026-10-23')))
            ->mapWithKeys(fn ($action): array => [$action->title => $action->daysLate])
            ->all();
    }

    it('keeps a task unchecked since an earlier scheduled day of the week, counting days late', function () {
        $task = todayTask('monday habit', ['days_of_week' => [1]]);

        expect(lateDays($task))->toBe(['monday habit' => 4]);
    });

    it('is not late when today is the first scheduled day or the task was checked since', function (array $days, array $completedOn) {
        $task = todayTask('habit', ['days_of_week' => $days], completedOn: $completedOn);

        expect(lateDays($task)['habit'] ?? 0)->toBe(0);
    })->with([
        'scheduled today only' => [[5], []],
        'checked on the scheduled day' => [[1], ['2026-10-19']],
        'checked on a later day' => [[1], ['2026-10-21']],
        'scheduled day was last week' => [[7], []],
    ]);

    it('measures lateness from the oldest scheduled day not covered by a check', function () {
        $task = todayTask('habit', ['days_of_week' => [1, 3]], completedOn: ['2026-10-19']);

        expect(lateDays($task))->toBe(['habit' => 2]);
    });

    it('is not late for days before the task existed', function () {
        $task = todayTask('new habit', ['days_of_week' => [1]]);
        $task->created_at = day('2026-10-21');

        expect(lateDays($task))->toBe([]);
    });

    it('keeps a task checked today in the list even on an unscheduled day', function () {
        $task = todayTask('extra', ['days_of_week' => [1]], completedOn: ['2026-10-23']);
        $actions = app(TodayListBuilder::class)->build(Collection::make([$task]), day('2026-10-23'));

        expect($actions)->toHaveCount(1)
            ->and($actions[0]->isDone)->toBeTrue()
            ->and($actions[0]->daysLate)->toBe(0);
    });
});
