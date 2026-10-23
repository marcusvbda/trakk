<?php

declare(strict_types=1);

use App\Enums\ActivityType;
use App\Enums\TaskStatus;
use App\Models\ChecklistItem;
use App\Models\Goal;
use App\Models\Task;
use App\Models\User;

beforeEach(function () {
    $this->travelTo(day('2026-10-23')->setTime(12, 0));
    $this->user = User::factory()->create();
    $this->project = Goal::factory()->for($this->user)->project()->create(['slug' => 'project']);
    $this->habit = Goal::factory()->for($this->user)->habit()->create(['slug' => 'habit']);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function taskInput(array $overrides = []): array
{
    return [
        'title' => 'Book sworn translation',
        'points' => 3,
        'notes' => 'Ask for a quote first.',
        'tag' => 'critical',
        'due_date' => '2026-11-05',
        ...$overrides,
    ];
}

describe('creating a task', function () {
    it('adds a one-off task to a project and logs it', function () {
        $this->actingAs($this->user)->post(route('goals.tasks.store', $this->project), taskInput())->assertRedirect();

        expect($this->project->tasks()->sole())
            ->title->toBe('Book sworn translation')
            ->points->toBe(3)
            ->tag->toBe('critical')
            ->status->toBe(TaskStatus::Todo)
            ->due_date->toDateString()->toBe('2026-11-05')
            ->and($this->user->activities()->where('type', ActivityType::TaskCreated)->count())->toBe(1);
    });

    it('starts a task in progress when asked', function () {
        $this->actingAs($this->user)->post(route('goals.tasks.store', $this->project), taskInput(['status' => 'doing']));

        expect($this->project->tasks()->sole()->status)->toBe(TaskStatus::Doing);
    });

    it('adds a repeating action with weekdays and a time to a habit', function () {
        $this->actingAs($this->user)->post(route('goals.tasks.store', $this->habit), [
            'title' => 'Speaking · 30 min',
            'points' => 3,
            'days_of_week' => [5, 1, 3],
            'scheduled_time' => '07:30',
        ]);

        expect($this->habit->tasks()->sole())->days_of_week->toBe([1, 3, 5])->scheduled_time->toStartWith('07:30');
    });

    it('rejects invalid input with an error per field', function (array $overrides, string $field) {
        $this->actingAs($this->user)
            ->post(route('goals.tasks.store', $this->project), taskInput($overrides))
            ->assertSessionHasErrors($field);

        expect(Task::query()->count())->toBe(0);
    })->with([
        'no title' => [['title' => ''], 'title'],
        'points above five' => [['points' => 6], 'points'],
        'points below one' => [['points' => 0], 'points'],
        'a bad date' => [['due_date' => '05/11/2026'], 'due_date'],
        'created as done' => [['status' => 'done'], 'status'],
        'weekdays on a project' => [['days_of_week' => [1]], 'days_of_week'],
        'a time on a project' => [['scheduled_time' => '07:30'], 'days_of_week'],
    ]);

    it('asks a habit for weekdays or a due date', function () {
        $this->actingAs($this->user)
            ->post(route('goals.tasks.store', $this->habit), ['title' => 'Walk', 'points' => 1])
            ->assertSessionHasErrors('days_of_week');
    });

    it('rejects an out-of-range or repeated weekday', function (array $days) {
        $this->actingAs($this->user)
            ->post(route('goals.tasks.store', $this->habit), ['title' => 'Walk', 'points' => 1, 'days_of_week' => $days])
            ->assertSessionHasErrors('days_of_week.0');
    })->with([
        'eight' => [[8]],
        'zero' => [[0]],
    ]);

    it('returns 404 for another user\'s goal', function () {
        $this->actingAs(User::factory()->create())
            ->post(route('goals.tasks.store', $this->project), taskInput())
            ->assertNotFound();

        expect(Task::query()->count())->toBe(0);
    });

    it('redirects guests to the sign in page', function () {
        $this->post(route('goals.tasks.store', $this->project), taskInput())->assertRedirect(route('login'));
    });
});

describe('editing a task', function () {
    it('updates a one-off task', function () {
        $task = Task::factory()->for($this->project)->dueOn(day('2026-11-01'))->create();

        $this->actingAs($this->user)
            ->patch(route('tasks.update', $task), taskInput(['title' => 'Renamed', 'points' => 1, 'tag' => null]))
            ->assertRedirect();

        expect($task->fresh())->title->toBe('Renamed')->points->toBe(1)->tag->toBeNull();
    });

    it('moves the task through a status change, so completions follow', function () {
        $task = Task::factory()->for($this->project)->dueOn(day('2026-11-01'))->create();

        $this->actingAs($this->user)->patch(route('tasks.update', $task), taskInput(['status' => 'done']));

        expect($task->fresh()->status)->toBe(TaskStatus::Done)->and($task->completions()->count())->toBe(1);
    });

    it('updates the weekdays and time of a repeating action', function () {
        $task = Task::factory()->for($this->habit)->recurring([1, 2])->create();

        $this->actingAs($this->user)->patch(route('tasks.update', $task), [
            'title' => 'Speaking',
            'points' => 2,
            'days_of_week' => [3, 4],
            'scheduled_time' => '08:00',
        ]);

        expect($task->fresh())->days_of_week->toBe([3, 4])->scheduled_time->toStartWith('08:00');
    });

    it('does not let a one-off task turn into a repeating one', function () {
        $task = Task::factory()->for($this->habit)->dueOn(day('2026-11-01'))->create();

        $this->actingAs($this->user)
            ->patch(route('tasks.update', $task), ['title' => 'x', 'points' => 1, 'days_of_week' => [1]])
            ->assertSessionHasErrors('days_of_week');
    });

    it('does not let a repeating action lose its weekdays', function () {
        $task = Task::factory()->for($this->habit)->recurring()->create();

        $this->actingAs($this->user)
            ->patch(route('tasks.update', $task), ['title' => 'x', 'points' => 1, 'due_date' => '2026-11-01'])
            ->assertSessionHasErrors('days_of_week');
    });

    it('does not let another user edit the task', function () {
        $task = Task::factory()->for($this->project)->create(['title' => 'Mine']);

        $this->actingAs(User::factory()->create())->patch(route('tasks.update', $task), taskInput())->assertForbidden();

        expect($task->fresh()->title)->toBe('Mine');
    });
});

describe('deleting a task', function () {
    it('removes the task with its checklist', function () {
        $task = Task::factory()->for($this->project)->create();
        ChecklistItem::factory()->for($task)->create();

        $this->actingAs($this->user)->delete(route('tasks.destroy', $task))->assertRedirect();

        expect(Task::query()->count())->toBe(0)->and(ChecklistItem::query()->count())->toBe(0);
    });

    it('does not let another user delete the task', function () {
        $task = Task::factory()->for($this->project)->create();

        $this->actingAs(User::factory()->create())->delete(route('tasks.destroy', $task))->assertForbidden();

        expect(Task::query()->count())->toBe(1);
    });
});

describe('the checklist', function () {
    beforeEach(function () {
        $this->task = Task::factory()->for($this->project)->create();
    });

    it('adds items in order', function () {
        $this->actingAs($this->user)->post(route('tasks.checklist.store', $this->task), ['title' => 'Get quote']);
        $this->actingAs($this->user)->post(route('tasks.checklist.store', $this->task), ['title' => 'Pay deposit']);

        expect($this->task->checklistItems()->orderBy('position')->pluck('title')->all())->toBe(['Get quote', 'Pay deposit']);
    });

    it('rejects an empty item', function () {
        $this->actingAs($this->user)
            ->post(route('tasks.checklist.store', $this->task), ['title' => ''])
            ->assertSessionHasErrors('title');
    });

    it('checks an item off and renames it', function () {
        $item = ChecklistItem::factory()->for($this->task)->create(['title' => 'Old', 'is_done' => false]);

        $this->actingAs($this->user)->patch(route('checklist.update', $item), ['is_done' => true]);
        expect($item->fresh())->is_done->toBeTrue()->title->toBe('Old');

        $this->actingAs($this->user)->patch(route('checklist.update', $item), ['title' => 'New', 'is_done' => false]);
        expect($item->fresh())->is_done->toBeFalse()->title->toBe('New');
    });

    it('unchecks an item', function () {
        $item = ChecklistItem::factory()->for($this->task)->create(['is_done' => true]);

        $this->actingAs($this->user)->patch(route('checklist.update', $item), ['is_done' => false]);

        expect($item->fresh()->is_done)->toBeFalse();
    });

    it('deletes an item', function () {
        $item = ChecklistItem::factory()->for($this->task)->create();

        $this->actingAs($this->user)->delete(route('checklist.destroy', $item))->assertRedirect();

        expect(ChecklistItem::query()->count())->toBe(0);
    });

    it('does not let another user touch the checklist', function () {
        $item = ChecklistItem::factory()->for($this->task)->create(['is_done' => false]);
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->post(route('tasks.checklist.store', $this->task), ['title' => 'x'])->assertForbidden();
        $this->actingAs($stranger)->patch(route('checklist.update', $item), ['is_done' => true])->assertForbidden();
        $this->actingAs($stranger)->delete(route('checklist.destroy', $item))->assertForbidden();

        expect($item->fresh()->is_done)->toBeFalse()->and($this->task->checklistItems()->count())->toBe(1);
    });
});
