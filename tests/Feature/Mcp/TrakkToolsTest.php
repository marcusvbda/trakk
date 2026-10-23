<?php

declare(strict_types=1);

use App\Enums\ActivitySource;
use App\Enums\ActivityType;
use App\Enums\TaskStatus;
use App\Mcp\Servers\TrakkServer;
use App\Mcp\Tools\CompleteTaskTool;
use App\Mcp\Tools\CreateGoalTool;
use App\Mcp\Tools\CreateTaskTool;
use App\Mcp\Tools\GetTodayTool;
use App\Mcp\Tools\ListGoalsTool;
use App\Mcp\Tools\MoveTaskTool;
use App\Mcp\Tools\ReplanTasksTool;
use App\Mcp\Tools\UncompleteTaskTool;
use App\Models\Activity;
use App\Models\Completion;
use App\Models\Goal;
use App\Models\Task;
use App\Models\User;
use App\Services\Dashboard\McpStatusService;
use Laravel\Mcp\Server\Testing\PendingTestResponse;

beforeEach(function () {
    $this->today = day('2026-10-23');
    $this->travelTo($this->today->setTime(12, 0));
    $this->user = User::factory()->create();
    $this->habit = Goal::factory()->habit()->for($this->user)->create(['title' => 'English']);
    $this->project = Goal::factory()->project()->for($this->user)->create(['title' => 'Nomad visa']);
});

function mcp(object $test): PendingTestResponse
{
    return TrakkServer::actingAs($test->user);
}

describe('get-today', function () {
    it('lists today\'s actions with their ids and the overdue tasks', function () {
        $speaking = Task::factory()->for($this->habit)->recurring()->create(['title' => 'Speaking · 30 min', 'points' => 3]);
        $late = Task::factory()->for($this->project)->overdueBy(2, $this->today)->create(['title' => 'Apostille diploma']);

        mcp($this)->tool(GetTodayTool::class)
            ->assertOk()
            ->assertSee(["[{$speaking->id}] Speaking · 30 min", "[{$late->id}] Apostille diploma"]);
    });

    it('logs the call so the top bar shows Claude as live', function () {
        mcp($this)->tool(GetTodayTool::class)->assertOk();

        expect(Activity::query()->where('type', ActivityType::McpCall)->where('user_id', $this->user->id)->count())->toBe(1);
    });
});

describe('list-goals', function () {
    it('lists the active goals with their ids', function () {
        mcp($this)->tool(ListGoalsTool::class)
            ->assertOk()
            ->assertSee(["[{$this->habit->id}] English", "[{$this->project->id}] Nomad visa"]);
    });

    it('does not show other users goals', function () {
        $stranger = Goal::factory()->create(['title' => 'Not yours']);

        mcp($this)->tool(ListGoalsTool::class)->assertOk()->assertDontSee($stranger->title);
    });
});

describe('complete-task', function () {
    beforeEach(function () {
        $this->task = Task::factory()->for($this->habit)->recurring()->points(3)->create(['title' => 'Speaking · 30 min']);
    });

    it('completes a task found by a loose title and returns the day score', function () {
        mcp($this)->tool(CompleteTaskTool::class, ['title' => 'speaking'])
            ->assertOk()
            ->assertSee('Done: "Speaking · 30 min" +3 pts.');

        expect(Completion::query()->where('task_id', $this->task->id)->count())->toBe(1)
            ->and(Activity::query()->where('source', ActivitySource::Mcp)->exists())->toBeTrue();
    });

    it('completes by id and says so when it was already done', function () {
        mcp($this)->tool(CompleteTaskTool::class, ['task_id' => $this->task->id])->assertOk();

        mcp($this)->tool(CompleteTaskTool::class, ['task_id' => $this->task->id])
            ->assertOk()
            ->assertSee('Already done today');
    });

    it('asks which task when the title is ambiguous', function () {
        Task::factory()->for($this->habit)->recurring()->create(['title' => 'Speaking · 15 min']);
        Task::factory()->for($this->habit)->recurring()->create(['title' => 'Speaking · 45 min']);

        mcp($this)->tool(CompleteTaskTool::class, ['title' => 'speaking'])
            ->assertHasErrors(['Several tasks match; pick one by id']);
    });

    it('errors when nothing matches or the task belongs to someone else', function () {
        $foreign = Task::factory()->for(Goal::factory()->habit())->recurring()->create(['title' => 'Speaking · 30 min']);

        mcp($this)->tool(CompleteTaskTool::class, ['title' => 'juggling'])->assertHasErrors(['No task matches']);
        mcp($this)->tool(CompleteTaskTool::class, ['task_id' => $foreign->id])->assertHasErrors(["No task with id {$foreign->id}"]);
        mcp($this)->tool(MoveTaskTool::class, ['task_id' => $foreign->id, 'status' => 'doing'])->assertHasErrors(["No task with id {$foreign->id}"]);
        mcp($this)->tool(UncompleteTaskTool::class, ['task_id' => $foreign->id])->assertHasErrors(["No task with id {$foreign->id}"]);
    });
});

describe('uncomplete-task', function () {
    it('removes today\'s completion', function () {
        $task = Task::factory()->for($this->habit)->recurring()->create(['title' => 'Speaking · 30 min']);
        mcp($this)->tool(CompleteTaskTool::class, ['task_id' => $task->id])->assertOk();

        mcp($this)->tool(UncompleteTaskTool::class, ['task_id' => $task->id])
            ->assertOk()
            ->assertSee('Unchecked: "Speaking · 30 min"');

        expect(Completion::query()->count())->toBe(0);
    });

    it('errors on an unknown task', function () {
        mcp($this)->tool(UncompleteTaskTool::class, ['task_id' => 999999])->assertHasErrors(['No task with id 999999']);
    });
});

describe('move-task', function () {
    it('moves a project task through the board', function () {
        $task = Task::factory()->for($this->project)->dueOn($this->today->addDays(3))->create(['title' => 'Apostille diploma']);

        mcp($this)->tool(MoveTaskTool::class, ['task_id' => $task->id, 'status' => 'doing'])
            ->assertOk()
            ->assertSee('Moved "Apostille diploma" to doing.');

        expect($task->refresh()->status)->toBe(TaskStatus::Doing);
    });

    it('rejects a recurring task and an invalid status', function () {
        $recurring = Task::factory()->for($this->habit)->recurring()->create();
        $oneOff = Task::factory()->for($this->project)->dueOn($this->today)->create();

        mcp($this)->tool(MoveTaskTool::class, ['task_id' => $recurring->id, 'status' => 'done'])->assertHasErrors(['no status']);
        mcp($this)->tool(MoveTaskTool::class, ['task_id' => $oneOff->id, 'status' => 'archived'])->assertHasErrors(['status']);
    });
});

describe('create-task', function () {
    it('adds a one-off task to a project goal and logs it as Claude', function () {
        mcp($this)->tool(CreateTaskTool::class, [
            'goal_id' => $this->project->id, 'title' => 'Book consulate slot', 'points' => 2, 'due_date' => '2026-10-30',
        ])->assertOk()->assertSee('Created');

        $task = Task::query()->where('title', 'Book consulate slot')->firstOrFail();

        expect($task->goal_id)->toBe($this->project->id)
            ->and($task->due_date->toDateString())->toBe('2026-10-30')
            ->and(Activity::query()->where('source', ActivitySource::Mcp)->where('type', ActivityType::TaskCreated)->exists())->toBeTrue();
    });

    it('adds a repeating action to a habit goal', function () {
        mcp($this)->tool(CreateTaskTool::class, [
            'goal_id' => $this->habit->id, 'title' => 'Shadowing', 'points' => 1, 'days_of_week' => [3, 1],
        ])->assertOk();

        expect(Task::query()->where('title', 'Shadowing')->firstOrFail()->days_of_week)->toBe([1, 3]);
    });

    it('refuses weekdays on a project, bad points and goals of other users', function () {
        $foreign = Goal::factory()->create();

        mcp($this)->tool(CreateTaskTool::class, ['goal_id' => $this->project->id, 'title' => 'X', 'points' => 2, 'days_of_week' => [1]])
            ->assertHasErrors(['Project tasks are one-off']);
        mcp($this)->tool(CreateTaskTool::class, ['goal_id' => $this->habit->id, 'title' => 'X', 'points' => 9, 'days_of_week' => [1]])
            ->assertHasErrors(['points']);
        mcp($this)->tool(CreateTaskTool::class, ['goal_id' => $this->project->id, 'title' => 'X', 'points' => 2, 'scheduled_time' => '09:00'])
            ->assertHasErrors(['no weekdays or time']);
        mcp($this)->tool(CreateTaskTool::class, ['goal_id' => $foreign->id, 'title' => 'X', 'points' => 2, 'due_date' => '2026-10-30'])
            ->assertHasErrors(['Not found']);

        expect(Task::query()->where('title', 'X')->exists())->toBeFalse();
    });
});

describe('create-goal', function () {
    it('creates a habit and returns its id', function () {
        mcp($this)->tool(CreateGoalTool::class, [
            'title' => 'Read daily', 'type' => 'habit', 'color' => 'cyan', 'priority' => 'medium',
            'start_date' => '2026-10-23', 'due_date' => '2026-12-31', 'target_per_week' => 5,
        ])->assertOk()->assertSee('Created goal');

        $goal = Goal::query()->where('title', 'Read daily')->firstOrFail();

        expect($goal->user_id)->toBe($this->user->id)
            ->and($goal->target_per_week)->toBe(5);
    });

    it('refuses a habit without a weekly target and a due date before the start', function () {
        mcp($this)->tool(CreateGoalTool::class, [
            'title' => 'Read daily', 'type' => 'habit', 'color' => 'cyan', 'priority' => 'medium',
            'start_date' => '2026-10-23', 'due_date' => '2026-12-31',
        ])->assertHasErrors(['A habit needs a target']);

        mcp($this)->tool(CreateGoalTool::class, [
            'title' => 'Ship it', 'type' => 'project', 'color' => 'blue', 'priority' => 'high',
            'start_date' => '2026-10-23', 'due_date' => '2026-10-01',
        ])->assertHasErrors(['The due date must be after the start date']);

        expect(Goal::query()->whereIn('title', ['Read daily', 'Ship it'])->exists())->toBeFalse();
    });
});

describe('replan-tasks', function () {
    it('moves overdue tasks to tomorrow', function () {
        $late = Task::factory()->for($this->project)->overdueBy(3, $this->today)->create();

        mcp($this)->tool(ReplanTasksTool::class, ['task_ids' => [$late->id], 'target' => 'tomorrow'])
            ->assertOk()
            ->assertSee('Replanned 1 task to 2026-10-24.');

        expect($late->refresh()->due_date->toDateString())->toBe('2026-10-24');
    });

    it('errors on a past date, a done task and ids that are not the owner\'s', function () {
        $open = Task::factory()->for($this->project)->overdueBy(1, $this->today)->create();
        $done = Task::factory()->for($this->project)->done()->create();
        $foreign = Task::factory()->create();

        mcp($this)->tool(ReplanTasksTool::class, ['task_ids' => [$open->id], 'target' => 'date', 'date' => '2026-10-01'])
            ->assertHasErrors(['today or later']);
        mcp($this)->tool(ReplanTasksTool::class, ['task_ids' => [$done->id], 'target' => 'today'])
            ->assertHasErrors(['Only open one-off tasks']);
        mcp($this)->tool(ReplanTasksTool::class, ['task_ids' => [$foreign->id], 'target' => 'today'])
            ->assertHasErrors(['do not exist']);
    });
});

describe('local server without a signed-in user', function () {
    it('acts for the owner account', function () {
        config(['trakk.owner.email' => $this->user->email]);
        Task::factory()->for($this->habit)->recurring()->create(['title' => 'Speaking · 30 min']);

        TrakkServer::tool(CompleteTaskTool::class, ['title' => 'speaking'])->assertOk()->assertSee('Done: "Speaking · 30 min"');

        expect(Completion::query()->count())->toBe(1);
    });

    it('fails when there is no owner account', function () {
        config(['trakk.owner.email' => 'nobody@example.com']);

        TrakkServer::tool(GetTodayTool::class)->assertHasErrors(['No owner account']);
    });
});

it('counts every registered tool for the top bar', function () {
    expect(app(McpStatusService::class)->toolCount())->toBe(8);
});

it('does not let a title without letters match every query', function () {
    Task::factory()->for($this->habit)->recurring()->create(['title' => '🔥']);
    $speaking = Task::factory()->for($this->habit)->recurring()->create(['title' => 'Speaking · 30 min']);

    mcp($this)->tool(CompleteTaskTool::class, ['title' => 'speaking'])->assertOk()->assertSee('"Speaking · 30 min"');

    expect(Completion::query()->where('task_id', $speaking->id)->count())->toBe(1);
});
