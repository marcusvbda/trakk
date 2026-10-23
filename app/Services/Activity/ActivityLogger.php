<?php

declare(strict_types=1);

namespace App\Services\Activity;

use App\Data\DayScore;
use App\Enums\ActivitySource;
use App\Enums\ActivityType;
use App\Enums\TaskStatus;
use App\Models\Activity;
use App\Models\Completion;
use App\Models\Goal;
use App\Models\Reminder;
use App\Models\Task;
use App\Models\User;
use App\Support\Clock;
use App\Support\Labels;
use Carbon\CarbonImmutable;

final class ActivityLogger
{
    private const string MCP_SUFFIX = ' · claude';

    public function __construct(
        private readonly Clock $clock,
        private readonly Labels $labels,
    ) {}

    public function taskCompleted(Task $task, Completion $completion, ActivitySource $source = ActivitySource::Web): Activity
    {
        $task->loadMissing('goal');

        return $this->recordForTask($task, ActivityType::TaskCompleted, $this->titleWithGoal($task), $source, $completion->points);
    }

    public function taskUndone(Task $task, ActivitySource $source = ActivitySource::Web): Activity
    {
        $task->loadMissing('goal');

        return $this->recordForTask($task, ActivityType::TaskUndone, $this->lower($task->title).' unchecked · '.$this->lower($task->goal->title), $source);
    }

    public function taskMoved(Task $task, TaskStatus $to, ActivitySource $source = ActivitySource::Web): Activity
    {
        return $this->recordForTask($task, ActivityType::TaskMoved, $this->lower($task->title).' → '.$to->value, $source);
    }

    public function taskReopened(Task $task, ActivitySource $source = ActivitySource::Web): Activity
    {
        return $this->recordForTask($task, ActivityType::TaskReopened, $this->lower($task->title).' reopened', $source);
    }

    public function taskCreated(Task $task, ActivitySource $source = ActivitySource::Web): Activity
    {
        $task->loadMissing('goal');

        return $this->recordForTask($task, ActivityType::TaskCreated, $this->lower($task->title).' added · '.$this->lower($task->goal->title), $source);
    }

    public function replanned(Task $task, CarbonImmutable $to, ActivitySource $source = ActivitySource::Web): Activity
    {
        return $this->recordForTask($task, ActivityType::Replanned, $this->lower($task->title).' → '.$this->labels->shortDate($to), $source);
    }

    public function goalCreated(Goal $goal, ActivitySource $source = ActivitySource::Web): Activity
    {
        return $this->record($goal->user_id, ActivityType::GoalCreated, 'goal created · '.$this->lower($goal->title), $source, $goal->id);
    }

    public function reminderEnabled(Reminder $reminder, ActivitySource $source = ActivitySource::Web): Activity
    {
        return $this->record($reminder->user_id, ActivityType::ReminderEnabled, $this->lower($reminder->label).' enabled', $source, $reminder->goal_id);
    }

    public function reminderPaused(Reminder $reminder, ActivitySource $source = ActivitySource::Web): Activity
    {
        return $this->record($reminder->user_id, ActivityType::ReminderPaused, $this->lower($reminder->label).' paused', $source, $reminder->goal_id);
    }

    /** The one check-in of the day: pass the day's earlier check-in to update it instead of adding another. */
    public function checkIn(User $user, DayScore $score, ?Activity $existing = null, ActivitySource $source = ActivitySource::Web): Activity
    {
        $activity = $existing ?? new Activity(['user_id' => $user->id]);
        $activity->fill([
            'type' => ActivityType::CheckIn,
            'message' => $this->checkInMessage($score, $source),
            'source' => $source,
            'meta' => ['date' => $score->date->toDateString(), 'score' => $score->points, 'target' => $score->target],
            'occurred_at' => $this->clock->now(),
        ])->save();

        return $activity;
    }

    private function checkInMessage(DayScore $score, ActivitySource $source): string
    {
        $message = $score->isTargetHit()
            ? 'check-in · target hit'
            : 'check-in · '.($score->target - $score->points).' pts short';

        return $this->withSource($message, $source);
    }

    private function recordForTask(Task $task, ActivityType $type, string $message, ActivitySource $source, ?int $points = null): Activity
    {
        $task->loadMissing('goal');

        return $this->record($task->goal->user_id, $type, $message, $source, $task->goal_id, $task->id, $points);
    }

    private function record(int $userId, ActivityType $type, string $message, ActivitySource $source, ?int $goalId = null, ?int $taskId = null, ?int $points = null): Activity
    {
        return Activity::query()->create([
            'user_id' => $userId,
            'goal_id' => $goalId,
            'task_id' => $taskId,
            'type' => $type,
            'message' => $this->withSource($message, $source),
            'points' => $points,
            'source' => $source,
            'occurred_at' => $this->clock->now(),
        ]);
    }

    private function titleWithGoal(Task $task): string
    {
        return $this->lower($task->title).' · '.$this->lower($task->goal->title);
    }

    private function withSource(string $message, ActivitySource $source): string
    {
        return $source === ActivitySource::Mcp ? $message.self::MCP_SUFFIX : $message;
    }

    private function lower(string $text): string
    {
        return mb_strtolower($text);
    }
}
