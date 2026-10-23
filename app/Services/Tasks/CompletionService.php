<?php

declare(strict_types=1);

namespace App\Services\Tasks;

use App\Enums\ActivitySource;
use App\Enums\TaskStatus;
use App\Models\Completion;
use App\Models\Task;
use App\Services\Activity\ActivityLogger;
use App\Support\Clock;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class CompletionService
{
    public function __construct(
        private readonly ActivityLogger $activity,
        private readonly Clock $clock,
    ) {}

    /** Checking an action twice on the same day keeps one completion and logs once. */
    public function complete(Task $task, CarbonImmutable $today, ActivitySource $source = ActivitySource::Web): Completion
    {
        return DB::transaction(function () use ($task, $today, $source): Completion {
            $completion = $this->record($task, $today);

            if ($completion->wasRecentlyCreated) {
                $this->activity->taskCompleted($task, $completion, $source);
            }

            return $completion;
        });
    }

    public function undo(Task $task, CarbonImmutable $today, ActivitySource $source = ActivitySource::Web): void
    {
        DB::transaction(function () use ($task, $today, $source): void {
            if ($this->revoke($task, $today)) {
                $this->activity->taskUndone($task, $source);
            }
        });
    }

    /**
     * Stores the completion without logging. A recurring task has one per scheduled day; a one-off task has
     * one in total and becomes done with it.
     */
    public function record(Task $task, CarbonImmutable $today): Completion
    {
        $task->loadMissing('goal');

        $attributes = ['user_id' => $task->goal->user_id, 'points' => $task->points];

        $completion = $task->isRecurring()
            ? $task->completions()->firstOrCreate(['completed_on' => $today->toDateString()], $attributes)
            : $task->completions()->first() ?? $task->completions()->create([...$attributes, 'completed_on' => $today]);

        if (! $task->isRecurring() && $task->status !== TaskStatus::Done) {
            $task->update(['status' => TaskStatus::Done, 'completed_at' => $this->clock->now()]);
        }

        return $completion;
    }

    /**
     * Deletes the completion without logging: today's for a recurring task, the only one for a one-off task,
     * which goes back to todo. Returns whether a completion was deleted.
     */
    public function revoke(Task $task, CarbonImmutable $today): bool
    {
        $completions = $task->completions();

        $deleted = $task->isRecurring()
            ? $completions->where('completed_on', $today->toDateString())->delete()
            : $completions->delete();

        if (! $task->isRecurring() && $task->status === TaskStatus::Done) {
            $task->update(['status' => TaskStatus::Todo, 'completed_at' => null]);
        }

        return $deleted > 0;
    }
}
