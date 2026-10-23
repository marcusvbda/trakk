<?php

declare(strict_types=1);

use App\Enums\ActivitySource;
use App\Enums\ActivityType;
use App\Enums\GoalStatus;
use App\Models\Goal;
use App\Models\Task;
use App\Models\User;

beforeEach(function () {
    $this->travelTo(day('2026-10-23')->setTime(12, 0));
    $this->user = User::factory()->create();
    $this->goal = Goal::factory()->for($this->user)->project()->create(['title' => 'Nomad visa · Spain']);
});

describe('tasks:scan-overdue', function () {
    it('warns once about each overdue task of an active goal', function () {
        Task::factory()->for($this->goal)->overdueBy(2, day('2026-10-23'))->create(['title' => 'Bank statements']);

        $this->artisan('tasks:scan-overdue')->assertSuccessful();
        $this->artisan('tasks:scan-overdue')->assertSuccessful();

        $warning = $this->user->activities()->where('type', ActivityType::TaskOverdue)->sole();
        expect($warning->message)->toBe('bank statements overdue')->and($warning->source)->toBe(ActivitySource::System);
    });

    it('leaves out what is not overdue, done, recurring or on a paused goal, and warns each owner only of their own', function () {
        $today = day('2026-10-23');
        $stranger = User::factory()->create();
        Task::factory()->for($this->goal)->dueOn($today)->create();
        Task::factory()->for($this->goal)->overdueBy(3, $today)->done()->create();
        Task::factory()->for(Goal::factory()->for($this->user)->habit()->create())->recurring()->create();
        Task::factory()->for(Goal::factory()->for($this->user)->paused()->create())->overdueBy(3, $today)->create();
        Task::factory()->for(Goal::factory()->for($stranger)->create())->overdueBy(3, $today)->create();

        $this->artisan('tasks:scan-overdue');

        expect($this->user->activities()->count())->toBe(0)
            ->and($stranger->activities()->where('type', ActivityType::TaskOverdue)->count())->toBe(1);
    });

    it('warns again when a replanned task becomes overdue on its new date', function () {
        $task = Task::factory()->for($this->goal)->overdueBy(2, day('2026-10-23'))->create();
        $this->artisan('tasks:scan-overdue');

        $task->update(['due_date' => day('2026-10-24')]);
        $this->travelTo(day('2026-10-26')->setTime(12, 0));
        $this->artisan('tasks:scan-overdue');

        expect($this->user->activities()->where('type', ActivityType::TaskOverdue)->count())->toBe(2);
    });
});

describe('goals:scan-deadlines', function () {
    it('warns 14 and 3 days before the due date, once each', function (int $daysLeft, int $warnings) {
        $this->goal->update(['start_date' => day('2026-09-01'), 'due_date' => day('2026-10-23')->addDays($daysLeft)]);

        $this->artisan('goals:scan-deadlines')->assertSuccessful();
        $this->artisan('goals:scan-deadlines')->assertSuccessful();

        expect($this->user->activities()->where('type', ActivityType::DeadlineNear)->count())->toBe($warnings);
    })->with([
        'fourteen days left' => [14, 1],
        'three days left' => [3, 1],
        'thirteen days left' => [13, 0],
        'a day left' => [1, 0],
        'a month left' => [30, 0],
    ]);

    it('says how many days are left', function () {
        $this->goal->update(['start_date' => day('2026-09-01'), 'due_date' => day('2026-11-06')]);

        $this->artisan('goals:scan-deadlines');

        expect($this->user->activities()->where('type', ActivityType::DeadlineNear)->sole()->message)->toBe('nomad visa due in 14 days');
    });

    it('skips goals that are not active', function () {
        $this->goal->update(['status' => GoalStatus::Paused, 'start_date' => day('2026-09-01'), 'due_date' => day('2026-11-06')]);

        $this->artisan('goals:scan-deadlines');

        expect($this->user->activities()->count())->toBe(0);
    });
});
