<?php

declare(strict_types=1);

use App\Data\GoalData;
use App\Enums\ActivityType;
use App\Enums\GoalColor;
use App\Enums\GoalStatus;
use App\Enums\GoalType;
use App\Enums\Priority;
use App\Models\Activity;
use App\Models\Goal;
use App\Models\Task;
use App\Models\User;
use App\Services\Goals\GoalService;

beforeEach(function () {
    $this->service = app(GoalService::class);
    $this->user = User::factory()->create();
});

function goalData(array $overrides = []): GoalData
{
    return new GoalData(...[
        'title' => 'Nomad visa · Spain',
        'type' => GoalType::Project,
        'color' => GoalColor::Amber,
        'priority' => Priority::High,
        'startDate' => day('2026-07-01'),
        'dueDate' => day('2027-01-15'),
        ...$overrides,
    ]);
}

describe('create', function () {
    it('creates an active goal with a slug from the title and logs it', function () {
        $goal = $this->service->create($this->user, goalData());

        expect($goal->slug)->toBe('nomad-visa-spain')
            ->and($goal->status)->toBe(GoalStatus::Active)
            ->and($goal->user_id)->toBe($this->user->id)
            ->and(Activity::query()->sole()->type)->toBe(ActivityType::GoalCreated);
    });

    it('numbers the slug when the user already has one, but not across users', function () {
        $first = $this->service->create($this->user, goalData(['title' => 'Read']));
        $second = $this->service->create($this->user, goalData(['title' => 'Read']));
        $third = $this->service->create($this->user, goalData(['title' => 'Read']));
        $other = $this->service->create(User::factory()->create(), goalData(['title' => 'Read']));

        expect([$first->slug, $second->slug, $third->slug, $other->slug])->toBe(['read', 'read-2', 'read-3', 'read']);
    });

    it('uses the slug it is given', function () {
        $goal = $this->service->create($this->user, goalData(['slug' => 'visa']));

        expect($goal->slug)->toBe('visa');
    });

    it('places each goal after the previous one', function () {
        $this->service->create($this->user, goalData(['title' => 'One']));
        $second = $this->service->create($this->user, goalData(['title' => 'Two']));

        expect($second->position)->toBe(2);
    });

    it('links a related goal of the same user', function () {
        $fund = $this->service->create($this->user, goalData(['title' => 'Moving fund']));

        $goal = $this->service->create($this->user, goalData(['relatedGoalId' => $fund->id]));

        expect($goal->related_goal_id)->toBe($fund->id);
    });

    it('rejects data that breaks a goal rule', function (array $overrides) {
        $this->service->create($this->user, goalData($overrides));
    })->throws(InvalidArgumentException::class)->with([
        'due on the start date' => [['dueDate' => day('2026-07-01')]],
        'due before the start date' => [['dueDate' => day('2026-06-01')]],
        'habit without a weekly target' => [['type' => GoalType::Habit]],
        'habit with target 0' => [['type' => GoalType::Habit, 'targetPerWeek' => 0]],
        'habit with target 8' => [['type' => GoalType::Habit, 'targetPerWeek' => 8]],
        'project with a weekly target' => [['targetPerWeek' => 3]],
    ]);

    it('rejects a related goal that belongs to someone else', function () {
        $foreign = Goal::factory()->create();

        $this->service->create($this->user, goalData(['relatedGoalId' => $foreign->id]));
    })->throws(InvalidArgumentException::class);
});

describe('update', function () {
    it('keeps the slug when the title changes', function () {
        $goal = $this->service->create($this->user, goalData());

        $this->service->update($goal, goalData(['title' => 'Renamed', 'priority' => Priority::Low]));

        expect($goal->refresh()->title)->toBe('Renamed')
            ->and($goal->slug)->toBe('nomad-visa-spain')
            ->and($goal->priority)->toBe(Priority::Low);
    });

    it('numbers a new slug that another goal of the user already has', function () {
        $this->service->create($this->user, goalData(['title' => 'Taken']));
        $goal = $this->service->create($this->user, goalData(['title' => 'Other']));

        $this->service->update($goal, goalData(['slug' => 'taken']));

        expect($goal->refresh()->slug)->toBe('taken-2');
    });

    it('lets a goal keep its own slug', function () {
        $goal = $this->service->create($this->user, goalData(['title' => 'Mine']));

        $this->service->update($goal, goalData(['slug' => 'mine']));

        expect($goal->refresh()->slug)->toBe('mine');
    });

    it('cannot be related to itself', function () {
        $goal = $this->service->create($this->user, goalData());

        $this->service->update($goal, goalData(['relatedGoalId' => $goal->id]));
    })->throws(InvalidArgumentException::class);

    it('changes the type only while the goal has no tasks', function () {
        $goal = $this->service->create($this->user, goalData());
        $habit = goalData(['type' => GoalType::Habit, 'targetPerWeek' => 5]);

        $this->service->update($goal, $habit);
        expect($goal->refresh()->type)->toBe(GoalType::Habit);

        Task::factory()->for($goal)->create();
        $this->service->update($goal, goalData());
    })->throws(DomainException::class);
});

describe('status and delete', function () {
    it('changes the status', function () {
        $goal = $this->service->create($this->user, goalData());

        $this->service->changeStatus($goal, GoalStatus::Paused);

        expect($goal->refresh()->status)->toBe(GoalStatus::Paused);
    });

    it('deletes the tasks with the goal and unlinks goals that pointed to it, keeping the log', function () {
        $fund = $this->service->create($this->user, goalData(['title' => 'Fund']));
        $visa = $this->service->create($this->user, goalData(['relatedGoalId' => $fund->id]));
        Task::factory()->for($fund)->create();

        $this->service->delete($fund);

        expect(Task::query()->count())->toBe(0)
            ->and($visa->refresh()->related_goal_id)->toBeNull()
            ->and(Activity::query()->count())->toBe(2)
            ->and(Activity::query()->whereNull('goal_id')->count())->toBe(1);
    });
});
