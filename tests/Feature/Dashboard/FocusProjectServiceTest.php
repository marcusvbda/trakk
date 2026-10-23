<?php

declare(strict_types=1);

use App\Data\Dashboard\FocusProjectData;
use App\Enums\GoalStatus;
use App\Models\ChecklistItem;
use App\Models\Goal;
use App\Models\Task;
use App\Models\User;
use App\Services\Dashboard\FocusProjectService;

beforeEach(function () {
    $this->today = day('2026-10-23');
    $this->user = User::factory()->create();
});

function focusFor(User $user, $today): ?FocusProjectData
{
    $goals = $user->goals()->active()->with('tasks')->get();

    return app(FocusProjectService::class)->for($goals, $today);
}

describe('for', function () {
    it('picks the most behind active project', function () {
        $onPace = Goal::factory()->for($this->user)->project()->window($this->today->subDays(50), $this->today->addDays(50))->create(['title' => 'On pace']);
        Task::factory()->for($onPace)->count(2)->done()->create();
        $behind = Goal::factory()->for($this->user)->project()->window($this->today->subDays(50), $this->today->addDays(50))->create(['title' => 'Behind']);
        Task::factory()->for($behind)->count(2)->create();

        expect(focusFor($this->user, $this->today)->title)->toBe('behind');
    });

    it('breaks a tie with the earliest due date', function () {
        $late = Goal::factory()->for($this->user)->project()->window($this->today->subDays(10), $this->today->addDays(90))->create(['title' => 'Late']);
        $soon = Goal::factory()->for($this->user)->project()->window($this->today->subDays(10), $this->today->addDays(30))->create(['title' => 'Soon']);

        expect(focusFor($this->user, $this->today)->title)->toBe('soon');
    });

    it('ignores habits and goals that are not active', function () {
        Goal::factory()->for($this->user)->habit()->create();
        Goal::factory()->for($this->user)->project()->status(GoalStatus::Paused)->create();

        expect(focusFor($this->user, $this->today))->toBeNull();
    });

    it('shows three cards per column and counts the rest, with checklist progress and overdue flags', function () {
        $goal = Goal::factory()->for($this->user)->project()->create();
        $tasks = Task::factory()->for($goal)->count(5)->sequence(fn ($sequence) => ['position' => $sequence->index])->create();
        $first = $tasks->first();
        ChecklistItem::factory()->for($first)->create(['is_done' => true]);
        ChecklistItem::factory()->for($first)->create(['is_done' => false]);
        $first->update(['due_date' => $this->today->subDay()]);

        $todo = focusFor($this->user, $this->today)->columns[0];

        expect($todo->cards)->toHaveCount(3)
            ->and($todo->hiddenCount)->toBe(2)
            ->and($todo->cards[0]->checklistDone)->toBe(1)
            ->and($todo->cards[0]->checklistTotal)->toBe(2)
            ->and($todo->cards[0]->isOverdue)->toBeTrue()
            ->and($todo->cards[1]->isOverdue)->toBeFalse();
    });
});
