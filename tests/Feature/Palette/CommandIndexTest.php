<?php

declare(strict_types=1);

use App\Models\Goal;
use App\Models\Task;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(day('2026-10-23')->setTime(12, 0));
    $this->user = User::factory()->create();
});

it('stays out of a normal page load', function () {
    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->missing('commandIndex'));
});

it('lists the owner\'s goals and today\'s tasks when the palette asks for it', function () {
    $goal = Goal::factory()->for($this->user)->habit()->create(['title' => 'Speak Spanish', 'slug' => 'speak-spanish']);
    Task::factory()->for($goal)->recurring(time: '07:30')->create(['title' => 'Speaking · 30 min']);
    Task::factory()->for($goal)->dueOn(day('2026-11-30'))->create(['title' => 'Not today']);
    Goal::factory()->for(User::factory()->create())->create(['title' => 'Someone else']);

    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->reloadOnly('commandIndex', fn (Assert $index) => $index
            ->has('commandIndex.goals', 1)
            ->where('commandIndex.goals.0.slug', 'speak-spanish')
            ->has('commandIndex.tasks', 1)
            ->where('commandIndex.tasks.0.title', 'Speaking · 30 min')
            ->where('commandIndex.tasks.0.isDone', false)));
});

it('keeps the tasks of paused goals out of today', function () {
    $goal = Goal::factory()->for($this->user)->habit()->paused()->create();
    Task::factory()->for($goal)->recurring()->create();

    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->reloadOnly('commandIndex', fn (Assert $index) => $index
            ->has('commandIndex.goals', 1)
            ->has('commandIndex.tasks', 0)));
});
