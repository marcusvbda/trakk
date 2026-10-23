<?php

declare(strict_types=1);

use App\Enums\GoalType;
use App\Models\ChecklistItem;
use App\Models\Completion;
use App\Models\Goal;
use App\Models\Reminder;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

describe('with the demo data', function () {
    beforeEach(function () {
        $this->owner = seedDemoOwner();
    });

    it('describes the nomad visa project from real data', function () {
        $this->actingAs($this->owner)
            ->get(route('goals.show', 'nomad-visa'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('goals/show')
                ->where('goal.slug', 'nomad-visa')
                ->where('goal.title', 'Nomad visa · Spain')
                ->where('goal.colorHex', '#F2B03D')
                ->where('goal.pace', ['status' => 'behind', 'delta' => -13])
                ->where('goal.facts.start', '01 jul 2026')
                ->where('goal.facts.dueDate', '15 jan 2027')
                ->where('goal.facts.remainingDays', 84)
                ->where('goal.facts.frequency', null)
                ->where('goal.facts.overdueCount', 2)
                ->where('progress.percent', 45)
                ->where('progress.expectedPercent', 58)
                ->where('progress.doneCount', 9)
                ->where('progress.totalCount', 20)
                ->where('progress.unit', 'tasks')
                ->where('progress.counts', ['todo' => 9, 'doing' => 2, 'done' => 9])
                ->where('actions', null));
    });

    it('lays every task out on the board by status, with the sheet data of each card', function () {
        $this->actingAs($this->owner)
            ->get(route('goals.show', 'nomad-visa'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('board.columns', 3)
                ->where('board.columns.0.status', 'todo')
                ->has('board.columns.0.cards', 9)
                ->where('board.columns.0.hiddenCount', 0)
                ->has('board.columns.1.cards', 2)
                ->has('board.columns.2.cards', 9)
                ->has('board.columns.0.cards.0', fn (Assert $card) => $card
                    ->hasAll(['id', 'title', 'dueLabel', 'checklistDone', 'checklistTotal', 'isOverdue', 'tag'])
                    ->has('task', fn (Assert $task) => $task
                        ->hasAll(['id', 'goalId', 'title', 'points', 'status', 'dueDate', 'scheduledTime', 'daysOfWeek', 'tag', 'notes', 'checklist']))));
    });

    it('charts the pace with an actual series that ends on the current progress', function () {
        $this->actingAs($this->owner)
            ->get(route('goals.show', 'nomad-visa'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('paceChart.totalDays', 198)
                ->where('paceChart.elapsedDays', 114)
                ->where('paceChart.startLabel', '01 jul')
                ->where('paceChart.dueLabel', '15 jan')
                ->where('paceChart.expectedPercent', 58)
                ->where('paceChart.paceUnit', 'tasks/wk')
                ->where('paceChart.caption', null)
                ->where('paceChart.ticks.0.label', 'sep')
                ->where('paceChart.ticks.1.label', 'nov')
                ->has('paceChart.actual', 5)
                ->where('paceChart.actual.0.percent', 0)
                ->where('paceChart.actual.4', ['day' => 114, 'percent' => 45]));
    });

    it('lists the recurring reminders first and the once-only ones last, with their channels', function () {
        $this->actingAs($this->owner)
            ->get(route('goals.show', 'nomad-visa'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('reminders.0.whenLabel', 'day before')
                ->where('reminders.1.whenLabel', 'monday')
                ->where('reminders.2.whenLabel', '01 jan')
                ->where('reminders.2.detailLabel', 'once')
                ->where('reminders.0.channels', ['telegram']));
    });

    it('lists the latest history entries of the goal first', function () {
        $this->actingAs($this->owner)
            ->get(route('goals.show', 'nomad-visa'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('history.0.text', 'bank statements due tomorrow · nomad visa')
                ->where('history.0.kind', 'warning'));
    });

    it('shows a habit goal with its recurring actions instead of a board', function () {
        $this->actingAs($this->owner)
            ->get(route('goals.show', Goal::query()->where('type', GoalType::Habit)->firstOrFail()->slug))
            ->assertInertia(fn (Assert $page) => $page
                ->where('board', null)
                ->where('progress.unit', 'days')
                ->where('progress.counts', null)
                ->has('progress.week.days', 7)
                ->where('paceChart.paceUnit', 'days/wk')
                ->has('actions.0', fn (Assert $action) => $action
                    ->hasAll(['id', 'title', 'days', 'timeLabel', 'points', 'weekLabel', 'task'])));
    });

    it('builds the page in a fixed number of queries', function () {
        $this->actingAs($this->owner);
        DB::enableQueryLog();

        $this->get(route('goals.show', 'nomad-visa'))->assertOk();

        expect(count(DB::getQueryLog()))->toBeLessThanOrEqual(16);
    });
});

describe('with no data', function () {
    beforeEach(function () {
        $this->travelTo(day('2026-10-23')->setTime(12, 0));
        $this->user = User::factory()->create();
    });

    it('gives a project without tasks empty props instead of nothing', function () {
        $goal = Goal::factory()->for($this->user)->project()->create([
            'slug' => 'bare',
            'description' => null,
            'done_criteria' => null,
            'area' => null,
        ]);

        $this->actingAs($this->user)
            ->get(route('goals.show', $goal->slug))
            ->assertInertia(fn (Assert $page) => $page
                ->where('goal.objective', ['text' => null, 'doneWhen' => null, 'area' => null, 'linkedTo' => null])
                ->where('progress.doneCount', 0)
                ->where('progress.totalCount', 0)
                ->where('progress.counts', ['todo' => 0, 'doing' => 0, 'done' => 0])
                ->where('paceChart.caption', 'add tasks to measure progress')
                ->has('board.columns', 3)
                ->where('board.columns.0.cards', [])
                ->where('board.columns.1.cards', [])
                ->where('board.columns.2.cards', [])
                ->where('reminders', [])
                ->where('history', []));
    });

    it('gives a habit without actions an empty list, not null', function () {
        $goal = Goal::factory()->for($this->user)->habit()->create(['slug' => 'bare-habit']);

        $this->actingAs($this->user)
            ->get(route('goals.show', $goal->slug))
            ->assertInertia(fn (Assert $page) => $page->where('actions', [])->where('board', null));
    });

    it('says when a goal has not started yet', function () {
        $goal = Goal::factory()->for($this->user)->window(day('2026-10-28'), day('2027-01-28'))->create();

        $this->actingAs($this->user)
            ->get(route('goals.show', $goal->slug))
            ->assertInertia(fn (Assert $page) => $page->where('paceChart.caption', 'starts in 5 days'));
    });
});

describe('what the page carries', function () {
    beforeEach(function () {
        $this->travelTo(day('2026-10-23')->setTime(12, 0));
        $this->user = User::factory()->create();
    });

    it('links the related goal and counts overdue tasks', function () {
        $fund = Goal::factory()->for($this->user)->create(['title' => 'Moving fund', 'slug' => 'moving-fund']);
        $goal = Goal::factory()->for($this->user)->create(['related_goal_id' => $fund->id]);
        Task::factory()->for($goal)->overdueBy(3, day('2026-10-23'))->create();
        Task::factory()->for($goal)->done()->overdueBy(3, day('2026-10-23'))->create();

        $this->actingAs($this->user)
            ->get(route('goals.show', $goal->slug))
            ->assertInertia(fn (Assert $page) => $page
                ->where('goal.objective.linkedTo', ['title' => 'Moving fund', 'slug' => 'moving-fund'])
                ->where('goal.facts.overdueCount', 1));
    });

    it('puts checklist items and the one-off or recurring shape in each task\'s sheet data', function () {
        $goal = Goal::factory()->for($this->user)->create();
        $task = Task::factory()->for($goal)->dueOn(day('2026-10-30'))->create(['notes' => 'bring the original', 'tag' => 'critical']);
        ChecklistItem::factory()->for($task)->create(['title' => 'call the office', 'is_done' => true, 'position' => 1]);

        $this->actingAs($this->user)
            ->get(route('goals.show', $goal->slug))
            ->assertInertia(fn (Assert $page) => $page
                ->where('board.columns.0.cards.0.task.dueDate', '2026-10-30')
                ->where('board.columns.0.cards.0.task.notes', 'bring the original')
                ->where('board.columns.0.cards.0.task.daysOfWeek', null)
                ->where('board.columns.0.cards.0.task.checklist.0.title', 'call the office')
                ->where('board.columns.0.cards.0.task.checklist.0.isDone', true)
                ->where('board.columns.0.cards.0.checklistDone', 1));
    });

    it('formats a habit action with its weekdays, time and this week\'s hits', function () {
        $goal = Goal::factory()->for($this->user)->habit()->create();
        $task = Task::factory()->for($goal)->recurring([1, 3, 5], '07:30')->points(2)->create();
        Completion::factory()->forTask($task)->on(day('2026-10-19'))->create();
        Completion::factory()->forTask($task)->on(day('2026-10-12'))->create();

        $this->actingAs($this->user)
            ->get(route('goals.show', $goal->slug))
            ->assertInertia(fn (Assert $page) => $page
                ->where('actions.0.days', [1, 3, 5])
                ->where('actions.0.timeLabel', '07:30')
                ->where('actions.0.points', 2)
                ->where('actions.0.weekLabel', '1/3 this week'));
    });

    it('shows once-only reminders by date and recurring ones by time', function () {
        $goal = Goal::factory()->for($this->user)->create();
        Reminder::factory()->forGoal($goal)->onDays([1])->create(['label' => 'Alert', 'time' => '09:00']);
        Reminder::factory()->forGoal($goal)->once(day('2027-01-01'))->create(['label' => 'Deadline', 'time' => '10:00']);

        $this->actingAs($this->user)
            ->get(route('goals.show', $goal->slug))
            ->assertInertia(fn (Assert $page) => $page
                ->where('reminders.0', fn ($reminder) => $reminder['whenLabel'] === 'monday' && $reminder['detailLabel'] === '09:00')
                ->where('reminders.1', fn ($reminder) => $reminder['whenLabel'] === '01 jan' && $reminder['detailLabel'] === 'once'));
    });
});

describe('ownership', function () {
    it('returns 404 for a goal that belongs to another user', function () {
        $goal = Goal::factory()->for(User::factory()->create())->create(['slug' => 'private']);

        $this->actingAs(User::factory()->create())->get(route('goals.show', 'private'))->assertNotFound();
    });

    it('resolves a slug that two users share to the signed-in user\'s own goal', function () {
        $mine = User::factory()->create();
        Goal::factory()->for(User::factory()->create())->create(['slug' => 'shared', 'title' => 'Theirs']);
        Goal::factory()->for($mine)->create(['slug' => 'shared', 'title' => 'Mine']);

        $this->actingAs($mine)
            ->get(route('goals.show', 'shared'))
            ->assertInertia(fn (Assert $page) => $page->where('goal.title', 'Mine'));
    });

    it('returns 404 for an unknown slug', function () {
        $this->actingAs(User::factory()->create())->get(route('goals.show', 'unknown-goal'))->assertNotFound();
    });
});
