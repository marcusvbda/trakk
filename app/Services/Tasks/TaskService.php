<?php

declare(strict_types=1);

namespace App\Services\Tasks;

use App\Data\ReplanData;
use App\Data\TaskData;
use App\Enums\ActivitySource;
use App\Enums\ReplanTarget;
use App\Enums\TaskStatus;
use App\Models\ChecklistItem;
use App\Models\Goal;
use App\Models\Task;
use App\Services\Activity\ActivityLogger;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class TaskService
{
    private const int MIN_POINTS = 1;

    private const int MAX_POINTS = 5;

    public function __construct(
        private readonly CompletionService $completions,
        private readonly ActivityLogger $activity,
    ) {}

    public function create(Goal $goal, TaskData $data, ActivitySource $source = ActivitySource::Web): Task
    {
        $this->assertValid($goal, $data);

        if ($data->status === TaskStatus::Done) {
            throw new InvalidArgumentException('A task cannot be created as done; complete it after creating it.');
        }

        return DB::transaction(function () use ($goal, $data, $source): Task {
            $task = $goal->tasks()->create([
                ...$this->attributes($data),
                'status' => $data->status ?? TaskStatus::Todo,
                'position' => (int) $goal->tasks()->max('position') + 1,
            ]);

            $this->activity->taskCreated($task->setRelation('goal', $goal), $source);

            return $task;
        });
    }

    /** A status change in `$data` goes through `moveTo`, so completions follow it. */
    public function update(Task $task, TaskData $data, CarbonImmutable $today, ActivitySource $source = ActivitySource::Web): Task
    {
        $task->loadMissing('goal');
        $this->assertValid($task->goal, $data);

        return DB::transaction(function () use ($task, $data, $today, $source): Task {
            $task->update($this->attributes($data));

            if (! $task->isRecurring() && $data->status !== null) {
                $this->moveTo($task, $data->status, $today, $source);
            }

            return $task->refresh();
        });
    }

    public function delete(Task $task): void
    {
        $task->delete();
    }

    /** Moving to done completes the task; moving out of done deletes that completion. */
    public function moveTo(Task $task, TaskStatus $to, CarbonImmutable $today, ActivitySource $source = ActivitySource::Web): Task
    {
        if ($task->isRecurring()) {
            throw new DomainException('Recurring tasks have no status; they are done per day.');
        }

        $from = $task->status;

        if ($from === $to) {
            return $task;
        }

        DB::transaction(function () use ($task, $from, $to, $today, $source): void {
            match (true) {
                $to === TaskStatus::Done => $this->finish($task, $today, $source),
                $from === TaskStatus::Done => $this->reopen($task, $to, $today, $source),
                default => $this->shift($task, $to, $source),
            };
        });

        return $task->refresh();
    }

    /**
     * @param  Collection<int, Task>  $tasks  open one-off tasks
     */
    public function replan(Collection $tasks, ReplanData $data, CarbonImmutable $today, ActivitySource $source = ActivitySource::Web): void
    {
        $dueDate = $this->replanDate($data, $today);

        $tasks->each(function (Task $task): void {
            if ($task->isRecurring() || $task->status === TaskStatus::Done) {
                throw new DomainException('Only open one-off tasks can be replanned.');
            }
        });

        DB::transaction(function () use ($tasks, $dueDate, $source): void {
            foreach ($tasks as $task) {
                $task->update(['due_date' => $dueDate]);
                $this->activity->replanned($task, $dueDate, $source);
            }
        });
    }

    public function addChecklistItem(Task $task, string $title): ChecklistItem
    {
        return $task->checklistItems()->create([
            'title' => $title,
            'position' => (int) $task->checklistItems()->max('position') + 1,
        ]);
    }

    public function updateChecklistItem(ChecklistItem $item, ?string $title = null, ?bool $isDone = null): ChecklistItem
    {
        $item->update(array_filter(
            ['title' => $title, 'is_done' => $isDone],
            fn (string|bool|null $value): bool => $value !== null,
        ));

        return $item;
    }

    public function deleteChecklistItem(ChecklistItem $item): void
    {
        $item->delete();
    }

    private function finish(Task $task, CarbonImmutable $today, ActivitySource $source): void
    {
        $completion = $this->completions->record($task, $today);
        $this->activity->taskCompleted($task, $completion, $source);
    }

    private function reopen(Task $task, TaskStatus $to, CarbonImmutable $today, ActivitySource $source): void
    {
        $this->completions->revoke($task, $today);

        if ($to !== TaskStatus::Todo) {
            $task->update(['status' => $to]);
        }

        $this->activity->taskReopened($task, $source);
    }

    private function shift(Task $task, TaskStatus $to, ActivitySource $source): void
    {
        $task->update(['status' => $to]);
        $this->activity->taskMoved($task, $to, $source);
    }

    private function replanDate(ReplanData $data, CarbonImmutable $today): CarbonImmutable
    {
        return match ($data->target) {
            ReplanTarget::Today => $today,
            ReplanTarget::Tomorrow => $today->addDay(),
            ReplanTarget::NextMonday => $today->next(CarbonInterface::MONDAY),
            ReplanTarget::Date => $this->pickedDate($data, $today),
        };
    }

    private function pickedDate(ReplanData $data, CarbonImmutable $today): CarbonImmutable
    {
        if ($data->date === null || $data->date->lessThan($today)) {
            throw new InvalidArgumentException('Pick a date that is today or later.');
        }

        return $data->date;
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(TaskData $data): array
    {
        return [
            'title' => $data->title,
            'notes' => $data->notes,
            'points' => $data->points,
            'due_date' => $data->dueDate,
            'scheduled_time' => $data->scheduledTime,
            'days_of_week' => $data->daysOfWeek === null ? null : $this->normalisedDays($data->daysOfWeek),
            'tag' => $data->tag,
        ];
    }

    /**
     * @param  list<int>  $days
     * @return list<int>
     */
    private function normalisedDays(array $days): array
    {
        $days = array_values(array_unique($days));
        sort($days);

        return $days;
    }

    private function assertValid(Goal $goal, TaskData $data): void
    {
        if ($data->points < self::MIN_POINTS || $data->points > self::MAX_POINTS) {
            throw new InvalidArgumentException('Points must be between 1 and 5.');
        }

        if ($goal->isProject() && $data->daysOfWeek !== null) {
            throw new InvalidArgumentException('Project tasks are one-off and take no weekdays.');
        }

        if ($goal->isHabit() && ($data->daysOfWeek === null || $data->daysOfWeek === [])) {
            throw new InvalidArgumentException('Habit actions need at least one weekday.');
        }

        foreach ($data->daysOfWeek ?? [] as $day) {
            if ($day < 1 || $day > 7) {
                throw new InvalidArgumentException('Weekdays are ISO numbers from 1 to 7.');
            }
        }
    }
}
