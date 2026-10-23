<?php

declare(strict_types=1);

use App\Data\ReplanData;
use App\Data\TaskData;
use App\Enums\ActivityType;
use App\Enums\ReplanTarget;
use App\Enums\TaskStatus;
use App\Models\Activity;
use App\Models\ChecklistItem;
use App\Models\Completion;
use App\Models\Goal;
use App\Models\Task;
use App\Models\User;
use App\Services\Tasks\TaskService;

beforeEach(function () {
    $this->service = app(TaskService::class);
    $this->user = User::factory()->create();
    $this->today = day('2026-10-23');
    $this->travelTo($this->today->setTime(12, 0));
    $this->project = Goal::factory()->project()->for($this->user)->create(['title' => 'Nomad visa']);
    $this->habit = Goal::factory()->habit()->for($this->user)->create();
});

function openTask(Goal $goal, array $state = []): Task
{
    return Task::factory()->for($goal)->dueOn(day('2026-10-20'))->create($state);
}

describe('create', function () {
    it('adds a one-off task at the end of the project and logs it', function () {
        openTask($this->project, ['position' => 4]);

        $task = $this->service->create($this->project, new TaskData('Apostille diploma', 3, dueDate: day('2026-10-30'), tag: 'critical'));

        expect($task->position)->toBe(5)
            ->and($task->status)->toBe(TaskStatus::Todo)
            ->and($task->tag)->toBe('critical')
            ->and(Activity::query()->sole()->message)->toBe('apostille diploma added · nomad visa');
    });

    it('stores habit weekdays sorted and without repeats', function () {
        $task = $this->service->create($this->habit, new TaskData('Speaking', 3, daysOfWeek: [5, 1, 3, 1], scheduledTime: '07:00'));

        expect($task->days_of_week)->toBe([1, 3, 5]);
    });

    it('lets a habit goal hold a one-off task with a due date', function () {
        $task = $this->service->create($this->habit, new TaskData('Full mock test', 4, dueDate: day('2026-10-26')));

        expect($task->isRecurring())->toBeFalse()
            ->and($task->due_date->toDateString())->toBe('2026-10-26');
    });

    it('rejects a task that does not fit the goal type or the rules', function (string $type, TaskData $data) {
        $goal = $type === 'habit' ? $this->habit : $this->project;

        $this->service->create($goal, $data);
    })->throws(InvalidArgumentException::class)->with([
        'weekdays on a project' => ['project', new TaskData('A', 1, daysOfWeek: [1])],
        'time on a project' => ['project', new TaskData('A', 1, scheduledTime: '09:00')],
        'empty weekdays' => ['habit', new TaskData('A', 1, daysOfWeek: [])],
        'habit task with neither weekdays nor a due date' => ['habit', new TaskData('A', 1)],
        'weekday out of range' => ['habit', new TaskData('A', 1, daysOfWeek: [8])],
        'zero points' => ['project', new TaskData('A', 0)],
        'six points' => ['project', new TaskData('A', 6)],
        'created as done' => ['project', new TaskData('A', 1, status: TaskStatus::Done)],
    ]);
});

describe('moveTo', function () {
    it('creates the completion with the task points when moved to done', function () {
        $task = openTask($this->project, ['points' => 4]);

        $this->service->moveTo($task, TaskStatus::Done, $this->today);

        $completion = Completion::query()->sole();
        expect($task->refresh()->status)->toBe(TaskStatus::Done)
            ->and($task->completed_at)->not->toBeNull()
            ->and($completion->points)->toBe(4)
            ->and($completion->completed_on->toDateString())->toBe('2026-10-23')
            ->and(Activity::query()->sole()->type)->toBe(ActivityType::TaskCompleted);
    });

    it('deletes that completion when moved out of done, whatever day it was', function () {
        $task = openTask($this->project);
        $this->service->moveTo($task, TaskStatus::Done, $this->today->subDays(5));

        $this->service->moveTo($task, TaskStatus::Doing, $this->today);

        expect(Completion::query()->count())->toBe(0)
            ->and($task->refresh()->status)->toBe(TaskStatus::Doing)
            ->and($task->completed_at)->toBeNull()
            ->and(Activity::query()->latest('id')->first()->type)->toBe(ActivityType::TaskReopened);
    });

    it('moves between open columns without touching completions', function () {
        $task = openTask($this->project);

        $this->service->moveTo($task, TaskStatus::Doing, $this->today);

        expect(Completion::query()->count())->toBe(0)
            ->and(Activity::query()->sole()->message)->toEndWith('→ doing');
    });

    it('is a no-op when the task is already in that status', function () {
        $task = openTask($this->project);

        $this->service->moveTo($task, TaskStatus::Todo, $this->today);

        expect(Activity::query()->count())->toBe(0);
    });

    it('refuses to give a recurring task a status', function () {
        $task = Task::factory()->for($this->habit)->recurring()->create();

        $this->service->moveTo($task, TaskStatus::Doing, $this->today);
    })->throws(DomainException::class);
});

describe('update', function () {
    it('edits the fields and keeps the status when none is given', function () {
        $task = openTask($this->project);
        $this->service->moveTo($task, TaskStatus::Done, $this->today);

        $this->service->update($task, new TaskData('Renamed', 2, dueDate: day('2026-10-21')), $this->today);

        expect($task->refresh()->title)->toBe('Renamed')
            ->and($task->status)->toBe(TaskStatus::Done)
            ->and(Completion::query()->count())->toBe(1);
    });

    it('moves the task when the data carries a new status', function () {
        $task = openTask($this->project);

        $this->service->update($task, new TaskData($task->title, $task->points, TaskStatus::Done, day('2026-10-20')), $this->today);

        expect(Completion::query()->count())->toBe(1);
    });
});

describe('update', function () {
    it('refuses to turn a recurring action into a one-off task or the reverse', function (bool $recurring) {
        $task = $recurring
            ? Task::factory()->for($this->habit)->recurring()->create()
            : Task::factory()->for($this->habit)->dueOn(day('2026-10-26'))->create();
        $data = $recurring ? new TaskData('X', 2, dueDate: day('2026-10-26')) : new TaskData('X', 2, daysOfWeek: [1]);

        $this->service->update($task, $data, $this->today);
    })->throws(DomainException::class)->with([
        'recurring to one-off' => [true],
        'one-off to recurring' => [false],
    ]);
});

describe('replan', function () {
    it('moves each task to the picked day and logs one entry per task', function (ReplanTarget $target, ?string $picked, string $expected) {
        $tasks = collect([openTask($this->project), openTask($this->project, ['due_date' => day('2026-10-18')])]);

        $this->service->replan($tasks, new ReplanData($target, $picked === null ? null : day($picked)), $this->today);

        expect($tasks->every(fn (Task $task): bool => $task->refresh()->due_date->toDateString() === $expected))->toBeTrue()
            ->and(Activity::query()->where('type', ActivityType::Replanned)->count())->toBe(2);
    })->with([
        'today' => [ReplanTarget::Today, null, '2026-10-23'],
        'tomorrow' => [ReplanTarget::Tomorrow, null, '2026-10-24'],
        'next monday from a friday' => [ReplanTarget::NextMonday, null, '2026-10-26'],
        'a picked date' => [ReplanTarget::Date, '2026-11-05', '2026-11-05'],
    ]);

    it('goes to the following Monday when today is a Monday', function () {
        $task = openTask($this->project);

        $this->service->replan(collect([$task]), new ReplanData(ReplanTarget::NextMonday), day('2026-10-26'));

        expect($task->refresh()->due_date->toDateString())->toBe('2026-11-02');
    });

    it('rejects a picked date in the past or a missing one', function (?string $picked) {
        $task = openTask($this->project);

        $this->service->replan(collect([$task]), new ReplanData(ReplanTarget::Date, $picked === null ? null : day($picked)), $this->today);
    })->throws(InvalidArgumentException::class)->with([
        'yesterday' => ['2026-10-22'],
        'none' => [null],
    ]);

    it('rejects tasks that are done and changes nothing', function () {
        $open = openTask($this->project);
        $done = openTask($this->project, ['status' => TaskStatus::Done]);

        try {
            $this->service->replan(collect([$open, $done]), new ReplanData(ReplanTarget::Today), $this->today);
        } catch (DomainException) {
        }

        expect($open->refresh()->due_date->toDateString())->toBe('2026-10-20')
            ->and(Activity::query()->count())->toBe(0);
    });
});

describe('checklist', function () {
    it('adds items in order, toggles and deletes them', function () {
        $task = openTask($this->project);

        $first = $this->service->addChecklistItem($task, 'Get quote');
        $second = $this->service->addChecklistItem($task, 'Pay deposit');
        $this->service->updateChecklistItem($first, isDone: true);
        $this->service->updateChecklistItem($second, title: 'Pay the deposit');
        $this->service->deleteChecklistItem($first);

        $items = ChecklistItem::query()->get();
        expect($second->position)->toBe(2)
            ->and($items)->toHaveCount(1)
            ->and($items->first()->title)->toBe('Pay the deposit')
            ->and($items->first()->is_done)->toBeFalse();
    });
});
