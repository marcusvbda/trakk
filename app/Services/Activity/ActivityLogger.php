<?php

declare(strict_types=1);

namespace App\Services\Activity;

use App\Data\DayScore;
use App\Enums\ActivitySource;
use App\Enums\ActivityType;
use App\Enums\ReminderChannel;
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

        return $this->recordForTask($task, ActivityType::TaskUndone, $this->lower($task->title).' unchecked · '.$this->labels->goalName($task->goal), $source);
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

        return $this->recordForTask($task, ActivityType::TaskCreated, $this->lower($task->title).' added · '.$this->labels->goalName($task->goal), $source);
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

    /** A read through MCP, so the top bar knows Claude is connected even when it only looked. */
    public function mcpCall(User $user, string $tool): Activity
    {
        return $this->record($user->id, ActivityType::McpCall, $tool, ActivitySource::Mcp);
    }

    public function reminderSent(Reminder $reminder, ReminderChannel $channel): Activity
    {
        return $this->record($reminder->user_id, ActivityType::ReminderSent, $this->lower($reminder->label).' sent · '.$channel->value, ActivitySource::System, $reminder->goal_id);
    }

    public function reminderFailed(Reminder $reminder, ReminderChannel $channel, CarbonImmutable $slot, string $error): Activity
    {
        return $this->record($reminder->user_id, ActivityType::ReminderFailed, $this->lower($reminder->label).' failed · '.$channel->value, ActivitySource::System, $reminder->goal_id, meta: ['reminder_id' => $reminder->id, 'slot' => $slot->toIso8601String(), 'error' => $error]);
    }

    public function taskOverdue(Task $task): Activity
    {
        return $this->record($task->goal->user_id, ActivityType::TaskOverdue, $this->lower($task->title).' overdue', ActivitySource::System, $task->goal_id, $task->id, meta: ['due' => $task->due_date?->toDateString()]);
    }

    public function deadlineNear(Goal $goal, int $days): Activity
    {
        return $this->record($goal->user_id, ActivityType::DeadlineNear, $this->labels->goalName($goal).' due in '.$days.' days', ActivitySource::System, $goal->id, meta: ['days' => $days]);
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

    /**
     * @param  array<string, mixed>|null  $meta
     */
    private function record(int $userId, ActivityType $type, string $message, ActivitySource $source, ?int $goalId = null, ?int $taskId = null, ?int $points = null, ?array $meta = null): Activity
    {
        return Activity::query()->create([
            'user_id' => $userId,
            'goal_id' => $goalId,
            'task_id' => $taskId,
            'type' => $type,
            'message' => $this->withSource($message, $source),
            'points' => $points,
            'source' => $source,
            'meta' => $meta,
            'occurred_at' => $this->clock->now(),
        ]);
    }

    private function titleWithGoal(Task $task): string
    {
        return $this->lower($task->title).' · '.$this->labels->goalName($task->goal);
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
