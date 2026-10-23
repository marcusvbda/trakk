<?php

declare(strict_types=1);

use App\Enums\ActivitySource;
use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\Completion;
use App\Models\Goal;
use App\Models\Task;
use App\Models\User;
use App\Services\Activity\CheckInService;

beforeEach(function () {
    $this->service = app(CheckInService::class);
    $this->user = User::factory()->create(['daily_points_target' => 14]);
    $this->today = day('2026-10-23');
    $this->travelTo($this->today->setTime(21, 30));
    $this->task = Task::factory()->for(Goal::factory()->habit()->for($this->user))->recurring()->create();
});

function scoreToday(User $user, Task $task, int $points, string $on = '2026-10-23'): void
{
    Completion::factory()->forTask($task)->on(day($on))->worth($points)->create();
}

describe('closeDay', function () {
    it('logs target hit with the score and target', function () {
        scoreToday($this->user, $this->task, 15);

        $activity = $this->service->closeDay($this->user, $this->today);

        expect($activity->type)->toBe(ActivityType::CheckIn)
            ->and($activity->message)->toBe('check-in · target hit')
            ->and($activity->meta)->toBe(['date' => '2026-10-23', 'score' => 15, 'target' => 14]);
    });

    it('logs how many points were missing', function () {
        scoreToday($this->user, $this->task, 9);

        expect($this->service->closeDay($this->user, $this->today)->message)->toBe('check-in · 5 pts short');
    });

    it('counts only the points of that day', function () {
        scoreToday($this->user, $this->task, 20, '2026-10-22');
        scoreToday($this->user, $this->task, 3);

        expect($this->service->closeDay($this->user, $this->today)->meta['score'])->toBe(3);
    });

    it('updates the first check-in of the day instead of adding a second', function () {
        scoreToday($this->user, $this->task, 9);
        $first = $this->service->closeDay($this->user, $this->today);

        scoreToday($this->user, Task::factory()->for($this->task->goal)->recurring()->create(), 6);
        $second = $this->service->closeDay($this->user, $this->today);

        expect(Activity::query()->count())->toBe(1)
            ->and($second->id)->toBe($first->id)
            ->and($second->message)->toBe('check-in · target hit');
    });

    it('keeps one check-in per day', function () {
        $this->service->closeDay($this->user, $this->today->subDay());
        $this->service->closeDay($this->user, $this->today);

        expect(Activity::query()->count())->toBe(2);
    });

    it('marks MCP check-ins', function () {
        $activity = $this->service->closeDay($this->user, $this->today, ActivitySource::Mcp);

        expect($activity->message)->toBe('check-in · 14 pts short · claude')
            ->and($activity->source)->toBe(ActivitySource::Mcp);
    });
});
