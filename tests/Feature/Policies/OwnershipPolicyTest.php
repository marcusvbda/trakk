<?php

declare(strict_types=1);

use App\Models\ChecklistItem;
use App\Models\Goal;
use App\Models\Reminder;
use App\Models\Task;
use App\Models\User;

describe('ownership policies', function () {
    beforeEach(function () {
        $this->owner = User::factory()->create();
        $this->stranger = User::factory()->create();
    });

    it('lets the owner act on their goal, task, checklist item and reminder', function () {
        $goal = Goal::factory()->for($this->owner)->create();
        $task = Task::factory()->for($goal)->create();
        $item = ChecklistItem::factory()->for($task)->create();
        $reminder = Reminder::factory()->for($this->owner)->create();

        expect($this->owner->can('update', $goal))->toBeTrue()
            ->and($this->owner->can('delete', $task))->toBeTrue()
            ->and($this->owner->can('update', $item))->toBeTrue()
            ->and($this->owner->can('delete', $reminder))->toBeTrue();
    });

    it('denies another user on every resource', function () {
        $goal = Goal::factory()->for($this->owner)->create();
        $task = Task::factory()->for($goal)->create();
        $item = ChecklistItem::factory()->for($task)->create();
        $reminder = Reminder::factory()->for($this->owner)->create();

        expect($this->stranger->can('view', $goal))->toBeFalse()
            ->and($this->stranger->can('update', $task))->toBeFalse()
            ->and($this->stranger->can('delete', $item))->toBeFalse()
            ->and($this->stranger->can('update', $reminder))->toBeFalse();
    });
});
