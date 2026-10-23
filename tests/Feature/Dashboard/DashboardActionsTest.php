<?php

declare(strict_types=1);

use App\Enums\ActivityType;
use App\Enums\TaskStatus;
use App\Models\Activity;
use App\Models\Goal;
use App\Models\Reminder;
use App\Models\Task;
use App\Models\User;

beforeEach(function () {
    $this->travelTo(day('2026-10-23')->setTime(12, 0));
    $this->today = day('2026-10-23');
    $this->user = User::factory()->create();
    $this->goal = Goal::factory()->for($this->user)->habit()->create();
});

describe('checking an action', function () {
    it('stores today\'s completion with the task points', function () {
        $task = Task::factory()->for($this->goal)->recurring()->points(3)->create();

        $this->actingAs($this->user)->post(route('tasks.completions.store', $task))->assertRedirect();

        expect($task->completions()->sole())
            ->points->toBe(3)
            ->completed_on->equalTo($this->today)->toBeTrue();
    });

    it('moves a one-off task to done', function () {
        $task = Task::factory()->for($this->goal)->dueOn($this->today)->create();

        $this->actingAs($this->user)->post(route('tasks.completions.store', $task));

        expect($task->fresh()->status)->toBe(TaskStatus::Done);
    });

    it('unchecks it again', function () {
        $task = Task::factory()->for($this->goal)->recurring()->create();
        $this->actingAs($this->user)->post(route('tasks.completions.store', $task));

        $this->actingAs($this->user)->delete(route('tasks.completions.destroy', $task))->assertRedirect();

        expect($task->completions()->count())->toBe(0);
    });

    it('does not let another user check the task', function () {
        $task = Task::factory()->for($this->goal)->recurring()->create();

        $this->actingAs(User::factory()->create())->post(route('tasks.completions.store', $task))->assertForbidden();

        expect($task->completions()->count())->toBe(0);
    });

    it('redirects guests to the sign in page', function () {
        $task = Task::factory()->for($this->goal)->recurring()->create();

        $this->post(route('tasks.completions.store', $task))->assertRedirect(route('login'));
    });
});

describe('moving a card', function () {
    beforeEach(function () {
        $this->project = Goal::factory()->for($this->user)->project()->create();
    });

    it('changes the task status', function () {
        $task = Task::factory()->for($this->project)->dueOn($this->today)->create();

        $this->actingAs($this->user)
            ->patch(route('tasks.status.update', $task), ['status' => 'doing'])
            ->assertRedirect();

        expect($task->fresh()->status)->toBe(TaskStatus::Doing);
    });

    it('rejects an unknown status', function () {
        $task = Task::factory()->for($this->project)->create();

        $this->actingAs($this->user)
            ->patch(route('tasks.status.update', $task), ['status' => 'blocked'])
            ->assertSessionHasErrors('status');
    });

    it('rejects a status for a recurring task', function () {
        $task = Task::factory()->for($this->goal)->recurring()->create();

        $this->actingAs($this->user)
            ->patch(route('tasks.status.update', $task), ['status' => 'done'])
            ->assertSessionHasErrors('status');
    });

    it('answers 403, not a validation error, for another user\'s recurring task', function () {
        $task = Task::factory()->for($this->goal)->recurring()->create();

        $this->actingAs(User::factory()->create())
            ->patch(route('tasks.status.update', $task), ['status' => 'done'])
            ->assertForbidden();
    });

    it('does not let another user move the task', function () {
        $task = Task::factory()->for($this->project)->create();

        $this->actingAs(User::factory()->create())
            ->patch(route('tasks.status.update', $task), ['status' => 'doing'])
            ->assertForbidden();
    });
});

describe('toggling a reminder', function () {
    it('pauses and enables it and logs both', function () {
        $reminder = Reminder::factory()->for($this->user)->create();

        $this->actingAs($this->user)->patch(route('reminders.active.update', $reminder), ['is_active' => false])->assertRedirect();
        expect($reminder->fresh()->is_active)->toBeFalse();

        $this->actingAs($this->user)->patch(route('reminders.active.update', $reminder), ['is_active' => true]);
        expect($reminder->fresh()->is_active)->toBeTrue()
            ->and(Activity::query()->pluck('type')->all())->toBe([ActivityType::ReminderPaused, ActivityType::ReminderEnabled]);
    });

    it('requires the new state', function () {
        $reminder = Reminder::factory()->for($this->user)->create();

        $this->actingAs($this->user)
            ->patch(route('reminders.active.update', $reminder), [])
            ->assertSessionHasErrors('is_active');
    });

    it('does not let another user toggle it', function () {
        $reminder = Reminder::factory()->for($this->user)->create();

        $this->actingAs(User::factory()->create())
            ->patch(route('reminders.active.update', $reminder), ['is_active' => false])
            ->assertForbidden();
    });
});

describe('replanning overdue tasks', function () {
    beforeEach(function () {
        $this->late = Task::factory()->for($this->goal)->overdueBy(3, $this->today)->create();
        $this->later = Task::factory()->for($this->goal)->overdueBy(1, $this->today)->create();
    });

    it('moves every selected task to the target date and logs each one', function (string $target, string $expected) {
        $this->actingAs($this->user)
            ->post(route('replan.store'), ['task_ids' => [$this->late->id, $this->later->id], 'target' => $target])
            ->assertRedirect();

        expect($this->late->fresh()->due_date->toDateString())->toBe($expected)
            ->and($this->later->fresh()->due_date->toDateString())->toBe($expected)
            ->and(Activity::query()->where('type', ActivityType::Replanned)->count())->toBe(2);
    })->with([
        'today' => ['today', '2026-10-23'],
        'tomorrow' => ['tomorrow', '2026-10-24'],
        'next monday' => ['next_monday', '2026-10-26'],
    ]);

    it('accepts a picked date', function () {
        $this->actingAs($this->user)
            ->post(route('replan.store'), ['task_ids' => [$this->late->id], 'target' => 'date', 'date' => '2026-11-02'])
            ->assertRedirect();

        expect($this->late->fresh()->due_date->toDateString())->toBe('2026-11-02');
    });

    it('rejects a picked date in the past', function () {
        $this->actingAs($this->user)
            ->post(route('replan.store'), ['task_ids' => [$this->late->id], 'target' => 'date', 'date' => '2026-10-20'])
            ->assertSessionHasErrors('date');
    });

    it('requires at least one task and a target', function () {
        $this->actingAs($this->user)
            ->post(route('replan.store'), ['task_ids' => []])
            ->assertSessionHasErrors(['task_ids', 'target']);
    });

    it('leaves another user\'s tasks alone', function () {
        $this->actingAs(User::factory()->create())
            ->post(route('replan.store'), ['task_ids' => [$this->late->id], 'target' => 'tomorrow'])
            ->assertSessionHasErrors('task_ids');

        expect($this->late->fresh()->due_date->toDateString())->toBe('2026-10-20');
    });

    it('refuses recurring and finished tasks', function () {
        $recurring = Task::factory()->for($this->goal)->recurring()->create();

        $this->actingAs($this->user)
            ->post(route('replan.store'), ['task_ids' => [$recurring->id], 'target' => 'tomorrow'])
            ->assertSessionHasErrors('task_ids');
    });
});

describe('closing the day', function () {
    it('logs one check-in and updates it when closed again', function () {
        $task = Task::factory()->for($this->goal)->recurring()->points(5)->create();

        $this->actingAs($this->user)->post(route('check-in.store'))->assertRedirect();
        $this->actingAs($this->user)->post(route('tasks.completions.store', $task));
        $this->actingAs($this->user)->post(route('check-in.store'));

        $checkIn = Activity::query()->where('type', ActivityType::CheckIn)->sole();

        expect($checkIn->message)->toBe('check-in · 9 pts short');
    });

    it('redirects guests to the sign in page', function () {
        $this->post(route('check-in.store'))->assertRedirect(route('login'));
    });
});
