<?php

declare(strict_types=1);

use App\Models\Completion;
use App\Models\Goal;
use App\Models\Task;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(day('2026-10-24'));
    $this->user = User::factory()->create();
    $this->goal = Goal::factory()->for($this->user)->habit()->create();
    $this->workout = Task::factory()->for($this->goal)->recurring([1, 2, 3, 4, 5])->create(['position' => 1]);
    $this->stretch = Task::factory()->for($this->goal)->recurring([6])->create(['position' => 2]);
});

describe('the weekly score', function () {
    it('counts every scheduled day of every action as one check, starting at week 1', function () {
        $this->actingAs($this->user)
            ->get(route('goals.show', $this->goal->slug))
            ->assertInertia(fn (Assert $page) => $page
                ->where('progress.weekly', ['weekNumber' => 1, 'doneCount' => 0, 'totalCount' => 6, 'percent' => 0])
                ->where('weeks', []));
    });

    it('counts checks given retroactively on earlier days of the week', function () {
        Completion::factory()->forTask($this->workout)->on(day('2026-10-20'))->create();
        Completion::factory()->forTask($this->workout)->on(day('2026-10-21'))->create();
        Completion::factory()->forTask($this->stretch)->on(day('2026-10-24'))->create();

        $this->actingAs($this->user)
            ->get(route('goals.show', $this->goal->slug))
            ->assertInertia(fn (Assert $page) => $page
                ->where('progress.weekly', ['weekNumber' => 1, 'doneCount' => 3, 'totalCount' => 6, 'percent' => 50]));
    });

    it('closes the week on Sunday night and logs it with the next week open', function () {
        Completion::factory()->forTask($this->workout)->on(day('2026-10-20'))->create();
        Completion::factory()->forTask($this->workout)->on(day('2026-10-21'))->create();
        $this->travelTo(day('2026-11-03'));

        $this->actingAs($this->user)
            ->get(route('goals.show', $this->goal->slug))
            ->assertInertia(fn (Assert $page) => $page
                ->where('progress.weekly.weekNumber', 3)
                ->where('progress.weekly.doneCount', 0)
                ->where('weeks.0', fn ($week) => $week['timeLabel'] === 'week 2' && $week['text'] === '0 of 6')
                ->where('weeks.1', fn ($week) => $week['timeLabel'] === 'week 1' && $week['text'] === '2 of 6' && $week['kind'] === 'warning'));
    });
});

describe('checking a day', function () {
    it('checks and unchecks a past day of the current week', function () {
        $this->actingAs($this->user)
            ->post(route('tasks.completions.store', ['task' => $this->workout, 'on' => '2026-10-22']))
            ->assertRedirect();

        expect($this->workout->completions()->sole()->completed_on->toDateString())->toBe('2026-10-22');

        $this->actingAs($this->user)
            ->delete(route('tasks.completions.destroy', ['task' => $this->workout, 'on' => '2026-10-22']))
            ->assertRedirect();

        expect($this->workout->completions()->count())->toBe(0);
    });

    it('exposes which days of the week can be toggled', function () {
        $this->actingAs($this->user)
            ->get(route('goals.show', $this->goal->slug))
            ->assertInertia(fn (Assert $page) => $page
                ->where('actions.0.weekDays.0', ['date' => '2026-10-19', 'scheduled' => true, 'done' => false, 'canToggle' => true])
                ->where('actions.0.weekDays.5', ['date' => '2026-10-24', 'scheduled' => false, 'done' => false, 'canToggle' => false]));
    });

    it('rejects future days, days of other weeks and unscheduled days', function (string $on) {
        $this->actingAs($this->user)
            ->post(route('tasks.completions.store', ['task' => $this->workout, 'on' => $on]))
            ->assertSessionHasErrors('on');

        expect($this->workout->completions()->count())->toBe(0);
    })->with([
        'a future day' => '2026-10-26',
        'the previous week' => '2026-10-16',
        'a day the action is not scheduled' => '2026-10-24',
    ]);
});
