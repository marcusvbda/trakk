<?php

declare(strict_types=1);

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Services\Tasks\OnTimeService;
use Carbon\CarbonImmutable;

function dueTask(string $due, ?string $completedAt = null, ?TaskStatus $status = null): Task
{
    return new Task([
        'due_date' => $due,
        'status' => $status ?? ($completedAt === null ? TaskStatus::Todo : TaskStatus::Done),
        'completed_at' => $completedAt === null ? null : CarbonImmutable::parse($completedAt, 'UTC'),
    ]);
}

describe('on time rate', function () {
    beforeEach(function () {
        $this->service = new OnTimeService;
        $this->today = day('2026-10-23');
    });

    it('divides tasks finished by their due date by all tasks due in the last 30 days', function () {
        $tasks = collect([
            dueTask('2026-10-10', '2026-10-09 10:00:00'),
            dueTask('2026-10-12', '2026-10-12 20:00:00'),
            dueTask('2026-10-15', '2026-10-17 08:00:00'),
            dueTask('2026-10-18'),
        ]);

        $rate = $this->service->rate($tasks, $this->today, 'UTC');

        expect($rate->delivered)->toBe(2)
            ->and($rate->total)->toBe(4)
            ->and($rate->percent)->toBe(50);
    });

    it('judges the completion day in the owner timezone', function () {
        $tasks = collect([dueTask('2026-10-10', '2026-10-10 23:30:00')]);

        expect($this->service->rate($tasks, $this->today, 'UTC')->delivered)->toBe(1)
            ->and($this->service->rate($tasks, $this->today, 'Pacific/Auckland')->delivered)->toBe(0);
    });

    it('leaves out tasks due before the window and open tasks due today', function () {
        $tasks = collect([
            dueTask('2026-09-23', '2026-09-23 10:00:00'),
            dueTask('2026-09-24', '2026-09-24 10:00:00'),
            dueTask('2026-10-23'),
        ]);

        $rate = $this->service->rate($tasks, $this->today, 'UTC');

        expect($rate->total)->toBe(1)
            ->and($rate->delivered)->toBe(1);
    });

    it('has no percentage without deadlines', function () {
        $rate = $this->service->rate(collect(), $this->today, 'UTC');

        expect($rate->percent)->toBeNull()
            ->and($rate->total)->toBe(0);
    });

    it('ignores recurring tasks', function () {
        $recurring = new Task(['due_date' => '2026-10-10', 'days_of_week' => [1], 'status' => TaskStatus::Todo]);

        expect($this->service->rate(collect([$recurring]), $this->today, 'UTC')->total)->toBe(0);
    });
});
