<?php

declare(strict_types=1);

use App\Enums\ActivityType;
use App\Enums\GoalColor;
use App\Enums\GoalStatus;
use App\Enums\GoalType;
use App\Enums\Priority;
use App\Models\Goal;
use App\Models\Task;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(day('2026-10-23')->setTime(12, 0));
    $this->user = User::factory()->create();
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function goalInput(array $overrides = []): array
{
    return [
        'title' => 'Run a half marathon',
        'type' => 'project',
        'color' => 'blue',
        'priority' => 'high',
        'start_date' => '2026-10-23',
        'due_date' => '2027-01-23',
        'target_per_week' => null,
        'area' => 'health',
        'description' => 'Train and race.',
        'done_criteria' => 'Cross the finish line.',
        'related_goal_id' => null,
        ...$overrides,
    ];
}

describe('creating a goal', function () {
    it('stores the goal with a slug from the title and opens its page', function () {
        $response = $this->actingAs($this->user)->post(route('goals.store'), goalInput());

        $goal = $this->user->goals()->sole();
        $response->assertRedirect(route('goals.show', $goal));

        expect($goal)
            ->slug->toBe('run-a-half-marathon')
            ->type->toBe(GoalType::Project)
            ->color->toBe(GoalColor::Blue)
            ->priority->toBe(Priority::High)
            ->status->toBe(GoalStatus::Active)
            ->and($this->user->activities()->where('type', ActivityType::GoalCreated)->count())->toBe(1);
    });

    it('stores a habit with its weekly target', function () {
        $this->actingAs($this->user)->post(route('goals.store'), goalInput(['type' => 'habit', 'target_per_week' => 5]));

        expect($this->user->goals()->sole())->type->toBe(GoalType::Habit)->target_per_week->toBe(5);
    });

    it('rejects invalid input with an error per field', function (array $overrides, string $field) {
        $this->actingAs($this->user)
            ->post(route('goals.store'), goalInput($overrides))
            ->assertSessionHasErrors($field);

        expect($this->user->goals()->count())->toBe(0);
    })->with([
        'no title' => [['title' => ''], 'title'],
        'unknown color' => [['color' => 'red'], 'color'],
        'due before start' => [['due_date' => '2026-10-01'], 'due_date'],
        'habit without a target' => [['type' => 'habit'], 'target_per_week'],
        'habit with a target above 7' => [['type' => 'habit', 'target_per_week' => 8], 'target_per_week'],
        'project with a target' => [['target_per_week' => 3], 'target_per_week'],
    ]);

    it('rejects a related goal of another user', function () {
        $foreign = Goal::factory()->for(User::factory()->create())->create();

        $this->actingAs($this->user)
            ->post(route('goals.store'), goalInput(['related_goal_id' => $foreign->id]))
            ->assertSessionHasErrors('related_goal_id');
    });

    it('redirects guests to the sign in page', function () {
        $this->post(route('goals.store'), goalInput())->assertRedirect(route('login'));
    });
});

describe('editing a goal', function () {
    beforeEach(function () {
        $this->goal = Goal::factory()->for($this->user)->project()->create(['title' => 'Old title', 'slug' => 'old-title']);
    });

    it('updates the fields and keeps the slug when none is sent', function () {
        $this->actingAs($this->user)
            ->patch(route('goals.update', $this->goal), goalInput(['title' => 'New title', 'color' => 'pink']))
            ->assertRedirect(route('goals.show', 'old-title'));

        expect($this->goal->fresh())->title->toBe('New title')->color->toBe(GoalColor::Pink)->slug->toBe('old-title');
    });

    it('moves the page to a new slug', function () {
        $this->actingAs($this->user)
            ->patch(route('goals.update', $this->goal), goalInput(['slug' => 'half-marathon']))
            ->assertRedirect(route('goals.show', 'half-marathon'));
    });

    it('rejects a slug another goal of the owner already uses', function () {
        Goal::factory()->for($this->user)->create(['slug' => 'taken']);

        $this->actingAs($this->user)
            ->patch(route('goals.update', $this->goal), goalInput(['slug' => 'taken']))
            ->assertSessionHasErrors('slug');
    });

    it('accepts a slug that another user already uses', function () {
        Goal::factory()->for(User::factory()->create())->create(['slug' => 'taken']);

        $this->actingAs($this->user)
            ->patch(route('goals.update', $this->goal), goalInput(['slug' => 'taken']))
            ->assertSessionDoesntHaveErrors();
    });

    it('rejects a slug that is not lowercase words and dashes', function () {
        $this->actingAs($this->user)
            ->patch(route('goals.update', $this->goal), goalInput(['slug' => 'Not A Slug']))
            ->assertSessionHasErrors('slug');
    });

    it('does not let the type change once the goal has tasks', function () {
        Task::factory()->for($this->goal)->create();

        $this->actingAs($this->user)
            ->patch(route('goals.update', $this->goal), goalInput(['type' => 'habit', 'target_per_week' => 3]))
            ->assertSessionHasErrors('type');

        expect($this->goal->fresh()->type)->toBe(GoalType::Project);
    });

    it('does not let a goal relate to itself', function () {
        $this->actingAs($this->user)
            ->patch(route('goals.update', $this->goal), goalInput(['related_goal_id' => $this->goal->id]))
            ->assertSessionHasErrors('related_goal_id');
    });

    it('returns 404 for another user\'s goal', function () {
        $this->actingAs(User::factory()->create())
            ->patch(route('goals.update', $this->goal), goalInput())
            ->assertNotFound();

        expect($this->goal->fresh()->title)->toBe('Old title');
    });
});

describe('changing the status', function () {
    beforeEach(function () {
        $this->goal = Goal::factory()->for($this->user)->create(['slug' => 'g']);
    });

    it('pauses and finishes a goal', function (string $status, GoalStatus $expected) {
        $this->actingAs($this->user)
            ->patch(route('goals.status.update', $this->goal), ['status' => $status])
            ->assertRedirect();

        expect($this->goal->fresh()->status)->toBe($expected);
    })->with([
        'paused' => ['paused', GoalStatus::Paused],
        'done' => ['done', GoalStatus::Done],
        'abandoned' => ['abandoned', GoalStatus::Abandoned],
        'active again' => ['active', GoalStatus::Active],
    ]);

    it('rejects an unknown status', function () {
        $this->actingAs($this->user)
            ->patch(route('goals.status.update', $this->goal), ['status' => 'archived'])
            ->assertSessionHasErrors('status');
    });

    it('returns 404 for another user\'s goal', function () {
        $this->actingAs(User::factory()->create())
            ->patch(route('goals.status.update', $this->goal), ['status' => 'paused'])
            ->assertNotFound();

        expect($this->goal->fresh()->status)->toBe(GoalStatus::Active);
    });
});

describe('deleting a goal', function () {
    it('removes the goal with its tasks and goes back to the dashboard', function () {
        $goal = Goal::factory()->for($this->user)->create(['slug' => 'g']);
        $task = Task::factory()->for($goal)->create();

        $this->actingAs($this->user)->delete(route('goals.destroy', $goal))->assertRedirect(route('dashboard'));

        expect(Goal::query()->count())->toBe(0)->and(Task::query()->whereKey($task->id)->exists())->toBeFalse();
    });

    it('returns 404 for another user\'s goal', function () {
        $goal = Goal::factory()->for($this->user)->create(['slug' => 'g']);

        $this->actingAs(User::factory()->create())->delete(route('goals.destroy', $goal))->assertNotFound();

        expect($goal->fresh())->not->toBeNull();
    });
});

describe('the goals list', function () {
    it('groups every goal by status and keeps the empty groups', function () {
        Goal::factory()->for($this->user)->create(['title' => 'Active one']);
        Goal::factory()->for($this->user)->paused()->create(['title' => 'Paused one']);

        $this->actingAs($this->user)
            ->get(route('goals.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('goals/index')
                ->has('groups', 4)
                ->where('groups.0.status', 'active')
                ->where('groups.0.goals.0.title', 'Active one')
                ->where('groups.1.status', 'paused')
                ->where('groups.1.goals.0.title', 'Paused one')
                ->where('groups.2', ['status' => 'done', 'goals' => []])
                ->where('groups.3', ['status' => 'abandoned', 'goals' => []]));
    });

    it('shows each goal with its dates and progress', function () {
        $this->user->delete();

        $this->actingAs(seedDemoOwner())
            ->get(route('goals.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('groups.0.goals', 6)
                ->has('groups.0.goals.0', fn (Assert $goal) => $goal
                    ->hasAll(['id', 'slug', 'title', 'type', 'priority', 'color', 'startLabel', 'dueLabel', 'percent'])));
    });

    it('lists only the signed-in user\'s goals', function () {
        Goal::factory()->for(User::factory()->create())->create();

        $this->actingAs($this->user)
            ->get(route('goals.index'))
            ->assertInertia(fn (Assert $page) => $page->where('groups.0.goals', []));
    });

    it('redirects guests to the sign in page', function () {
        $this->get(route('goals.index'))->assertRedirect(route('login'));
    });
});

describe('what the sheets need', function () {
    it('shares the goal options and date defaults with every page', function () {
        Goal::factory()->for($this->user)->create(['title' => 'First', 'slug' => 'first']);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('sheetOptions.today', '2026-10-23')
                ->where('sheetOptions.defaultDueDate', '2027-01-21')
                ->where('sheetOptions.goals.0.title', 'First')
                ->where('sheetOptions.goals.0.status', 'active'));
    });

    it('gives the goal page the raw values the edit sheet fills in', function () {
        $goal = Goal::factory()->for($this->user)->project()->create(['slug' => 'g', 'area' => 'health']);
        Task::factory()->for($goal)->create();

        $this->actingAs($this->user)
            ->get(route('goals.show', $goal))
            ->assertInertia(fn (Assert $page) => $page
                ->where('form.slug', 'g')
                ->where('form.type', 'project')
                ->where('form.area', 'health')
                ->where('form.status', 'active')
                ->where('form.hasTasks', true));
    });
});

describe('another user\'s goals in a form', function () {
    it('rejects a related goal of another user when editing', function () {
        $goal = Goal::factory()->for($this->user)->create(['slug' => 'g']);
        $foreign = Goal::factory()->for(User::factory()->create())->create();

        $this->actingAs($this->user)
            ->patch(route('goals.update', $goal), goalInput(['related_goal_id' => $foreign->id]))
            ->assertSessionHasErrors('related_goal_id');
    });
});
