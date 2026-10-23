<?php

declare(strict_types=1);

use App\Models\ChecklistItem;
use App\Models\Goal;
use App\Models\Reminder;
use App\Models\Task;
use App\Models\User;

beforeEach(function () {
    $this->travelTo(day('2026-10-23')->setTime(12, 0));
    $this->user = User::factory()->create();
    $this->goal = Goal::factory()->for($this->user)->project()->create(['slug' => 'g']);
    $this->task = Task::factory()->for($this->goal)->overdueBy(2, day('2026-10-23'))->create();
});

it('tells the owner what a write operation did', function (string $message, Closure $request) {
    $request($this)->assertSessionHas('inertia.flash_data.toast', ['type' => 'success', 'message' => $message]);
})->with([
    'goal created' => ['goal created', fn ($t) => $t->actingAs($t->user)->post(route('goals.store'), ['title' => 'New', 'type' => 'project', 'color' => 'blue', 'priority' => 'low', 'start_date' => '2026-10-23', 'due_date' => '2027-01-01'])],
    'goal saved' => ['goal saved', fn ($t) => $t->actingAs($t->user)->patch(route('goals.update', $t->goal), ['title' => 'Renamed', 'type' => 'project', 'color' => 'blue', 'priority' => 'low', 'start_date' => '2026-10-23', 'due_date' => '2027-01-01'])],
    'goal status' => ['goal set to paused', fn ($t) => $t->actingAs($t->user)->patch(route('goals.status.update', $t->goal), ['status' => 'paused'])],
    'goal deleted' => ['goal deleted', fn ($t) => $t->actingAs($t->user)->delete(route('goals.destroy', $t->goal))],
    'task added' => ['task added', fn ($t) => $t->actingAs($t->user)->post(route('goals.tasks.store', $t->goal), ['title' => 'New', 'points' => 1])],
    'task saved' => ['task saved', fn ($t) => $t->actingAs($t->user)->patch(route('tasks.update', $t->task), ['title' => 'Renamed', 'points' => 1, 'due_date' => '2026-10-30'])],
    'task deleted' => ['task deleted', fn ($t) => $t->actingAs($t->user)->delete(route('tasks.destroy', $t->task))],
    'item added' => ['checklist item added', fn ($t) => $t->actingAs($t->user)->post(route('tasks.checklist.store', $t->task), ['title' => 'Call'])],
    'item renamed' => ['checklist item saved', fn ($t) => $t->actingAs($t->user)->patch(route('checklist.update', ChecklistItem::factory()->for($t->task)->create()), ['title' => 'Call back'])],
    'item removed' => ['checklist item removed', fn ($t) => $t->actingAs($t->user)->delete(route('checklist.destroy', ChecklistItem::factory()->for($t->task)->create()))],
    'reminder added' => ['reminder added', fn ($t) => $t->actingAs($t->user)->post(route('reminders.store'), ['kind' => 'custom', 'label' => 'Stretch', 'time' => '08:00', 'days_of_week' => [1], 'channels' => ['telegram'], 'is_active' => true])],
    'reminder saved' => ['reminder saved', fn ($t) => $t->actingAs($t->user)->patch(route('reminders.update', Reminder::factory()->for($t->user)->create()), ['kind' => 'custom', 'label' => 'Stretch', 'time' => '08:00', 'days_of_week' => [1], 'channels' => ['telegram'], 'is_active' => true])],
    'reminder deleted' => ['reminder deleted', fn ($t) => $t->actingAs($t->user)->delete(route('reminders.destroy', Reminder::factory()->for($t->user)->create()))],
    'reminder paused' => ['reminder paused', fn ($t) => $t->actingAs($t->user)->patch(route('reminders.active.update', Reminder::factory()->for($t->user)->create()), ['is_active' => false])],
    'reminder enabled' => ['reminder enabled', fn ($t) => $t->actingAs($t->user)->patch(route('reminders.active.update', Reminder::factory()->for($t->user)->inactive()->create()), ['is_active' => true])],
    'replan' => ['1 task replanned', fn ($t) => $t->actingAs($t->user)->post(route('replan.store'), ['task_ids' => [$t->task->id], 'target' => 'today'])],
    'check-in' => ['check-in · 14 pts short', fn ($t) => $t->actingAs($t->user)->post(route('check-in.store'))],
    'profile' => ['profile saved', fn ($t) => $t->actingAs($t->user)->patch(route('profile.update'), ['name' => 'Name', 'email' => 'new@example.com', 'timezone' => 'Europe/Dublin', 'daily_points_target' => 14])],
]);

it('stays silent for the quick interactions that already show their result', function (Closure $request) {
    $request($this)->assertSessionMissing('inertia.flash_data');
})->with([
    'checking an action' => [fn ($t) => $t->actingAs($t->user)->post(route('tasks.completions.store', $t->task))],
    'moving a card' => [fn ($t) => $t->actingAs($t->user)->patch(route('tasks.status.update', $t->task), ['status' => 'doing'])],
    'ticking a checklist item' => [fn ($t) => $t->actingAs($t->user)->patch(route('checklist.update', ChecklistItem::factory()->for($t->task)->create()), ['is_done' => true])],
]);

it('does not toast a failed operation', function () {
    $this->actingAs($this->user)
        ->post(route('goals.tasks.store', $this->goal), ['title' => '', 'points' => 1])
        ->assertSessionHasErrors('title')
        ->assertSessionMissing('inertia.flash_data');
});
