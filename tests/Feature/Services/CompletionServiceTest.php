<?php

declare(strict_types=1);

use App\Enums\ActivitySource;
use App\Enums\ActivityType;
use App\Enums\TaskStatus;
use App\Models\Activity;
use App\Models\Completion;
use App\Models\Goal;
use App\Models\Task;
use App\Models\User;
use App\Services\Tasks\CompletionService;

beforeEach(function () {
    $this->service = app(CompletionService::class);
    $this->user = User::factory()->create();
    $this->today = day('2026-10-23');
    $this->travelTo($this->today->setTime(12, 0));
});

function habitTask(User $user, int $points = 3): Task
{
    return Task::factory()->for(Goal::factory()->habit()->for($user))->recurring()->points($points)->create();
}

function oneOffTask(User $user): Task
{
    return Task::factory()->for(Goal::factory()->project()->for($user))->dueOn(day('2026-10-23'))->points(4)->create();
}

describe('complete', function () {
    it('stores a completion for the owner with the points of the task', function () {
        $task = habitTask($this->user, 3);

        $completion = $this->service->complete($task, $this->today);

        expect($completion->user_id)->toBe($this->user->id)
            ->and($completion->points)->toBe(3)
            ->and($completion->completed_on->toDateString())->toBe('2026-10-23');
    });

    it('keeps one completion and one log entry when the same day is completed twice', function () {
        $task = habitTask($this->user);

        $this->service->complete($task, $this->today);
        $this->service->complete($task, $this->today);

        expect(Completion::query()->count())->toBe(1)
            ->and(Activity::query()->where('type', ActivityType::TaskCompleted)->count())->toBe(1);
    });

    it('keeps the points it was completed with when the task is edited later', function () {
        $task = habitTask($this->user, 3);
        $this->service->complete($task, $this->today);

        $task->update(['points' => 5]);

        expect(Completion::query()->sole()->points)->toBe(3);
    });

    it('completes a recurring task once per day', function () {
        $task = habitTask($this->user);

        $this->service->complete($task, $this->today);
        $this->service->complete($task, $this->today->addDay());

        expect(Completion::query()->count())->toBe(2)
            ->and($task->refresh()->status)->toBe(TaskStatus::Todo);
    });

    it('finishes a one-off task and never completes it twice', function () {
        $task = oneOffTask($this->user);

        $this->service->complete($task, $this->today);
        $this->service->complete($task, $this->today->addDay());

        expect(Completion::query()->count())->toBe(1)
            ->and($task->refresh()->status)->toBe(TaskStatus::Done)
            ->and($task->completed_at)->not->toBeNull();
    });

    it('logs the task, its goal and the points, and marks MCP calls', function () {
        $task = habitTask($this->user, 3);
        $task->update(['title' => 'Speaking · 30 min']);
        $task->goal->update(['title' => 'IELTS 7.0']);

        $this->service->complete($task, $this->today, ActivitySource::Mcp);

        $activity = Activity::query()->sole();
        expect($activity->message)->toBe('speaking · 30 min · ielts 7.0 · claude')
            ->and($activity->points)->toBe(3)
            ->and($activity->source)->toBe(ActivitySource::Mcp);
    });
});

describe('undo', function () {
    it('deletes only the completion of that day for a recurring task', function () {
        $task = habitTask($this->user);
        $this->service->complete($task, $this->today->subDay());
        $this->service->complete($task, $this->today);

        $this->service->undo($task, $this->today);

        expect(Completion::query()->sole()->completed_on->toDateString())->toBe('2026-10-22')
            ->and(Activity::query()->where('type', ActivityType::TaskUndone)->count())->toBe(1);
    });

    it('reopens a one-off task whatever day it was completed', function () {
        $task = oneOffTask($this->user);
        $this->service->complete($task, $this->today->subDays(3));

        $this->service->undo($task, $this->today);

        expect(Completion::query()->count())->toBe(0)
            ->and($task->refresh()->status)->toBe(TaskStatus::Todo)
            ->and($task->completed_at)->toBeNull();
    });

    it('does nothing and logs nothing when there is nothing to undo', function () {
        $task = habitTask($this->user);

        $this->service->undo($task, $this->today);

        expect(Activity::query()->count())->toBe(0);
    });
});
