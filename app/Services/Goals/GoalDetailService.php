<?php

declare(strict_types=1);

namespace App\Services\Goals;

use App\Data\Dashboard\LogEntryData;
use App\Data\Dashboard\PaceData;
use App\Data\Goals\GoalBoardData;
use App\Data\Goals\GoalDetailData;
use App\Data\Goals\GoalFactsData;
use App\Data\Goals\GoalFormData;
use App\Data\Goals\GoalLinkData;
use App\Data\Goals\GoalObjectiveData;
use App\Data\Goals\GoalOverviewData;
use App\Data\Goals\GoalProgressData;
use App\Data\Goals\GoalReminderData;
use App\Data\Goals\HabitActionData;
use App\Enums\ReminderChannel;
use App\Enums\TaskStatus;
use App\Models\Completion;
use App\Models\Goal;
use App\Models\Reminder;
use App\Models\Task;
use App\Services\Activity\LogEntryBuilder;
use App\Services\Reminders\ReminderFormBuilder;
use App\Services\Tasks\KanbanBuilder;
use App\Services\Tasks\TaskSheetBuilder;
use App\Support\Labels;
use Carbon\CarbonImmutable;

/**
 * Builds the goal page from a fixed set of queries: the goal with its tasks, completions, checklist items,
 * related goal, owner and reminders, plus the latest activities.
 */
final class GoalDetailService
{
    private const int HISTORY_ENTRIES = 20;

    public function __construct(
        private readonly GoalProgressService $progress,
        private readonly PaceChartBuilder $paceChart,
        private readonly KanbanBuilder $kanban,
        private readonly TaskSheetBuilder $sheets,
        private readonly LogEntryBuilder $logEntries,
        private readonly ReminderFormBuilder $reminderForms,
        private readonly Labels $labels,
    ) {}

    public function for(Goal $goal, CarbonImmutable $today): GoalDetailData
    {
        $goal->load([
            'user',
            'relatedGoal',
            'tasks' => fn ($query) => $query->orderBy('position'),
            'tasks.completions',
            'tasks.checklistItems',
            'reminders' => fn ($query) => $query->orderByRaw('fire_on is not null')->orderBy('id'),
        ]);

        return new GoalDetailData(
            $this->overview($goal, $today),
            $this->form($goal),
            $this->progressData($goal, $today),
            $this->paceChart->build($goal, $today),
            $goal->isProject() ? new GoalBoardData($this->kanban->columns($goal->tasks, $today)) : null,
            $goal->isHabit() ? $this->habitActions($goal, $today) : null,
            $goal->reminders->map(fn (Reminder $reminder): GoalReminderData => $this->reminder($reminder))->values()->all(),
            $this->history($goal, $today),
        );
    }

    private function overview(Goal $goal, CarbonImmutable $today): GoalOverviewData
    {
        $pace = $this->progress->pace($goal, $today);

        return new GoalOverviewData(
            $goal->id,
            $goal->slug,
            $goal->title,
            $goal->type,
            $goal->priority,
            $goal->color,
            $goal->color->hex(),
            new PaceData($pace->status, $pace->delta),
            new GoalFactsData(
                $this->labels->fullDate($goal->start_date),
                $this->labels->fullDate($goal->due_date),
                max(0, (int) $today->diffInDays($goal->due_date, false)),
                $goal->isHabit() ? $this->labels->goalFrequency($goal) : null,
                $goal->tasks->filter(fn (Task $task): bool => $task->isOverdueOn($today))->count(),
            ),
            new GoalObjectiveData(
                $goal->description,
                $goal->done_criteria,
                $goal->area,
                $goal->relatedGoal === null ? null : new GoalLinkData($goal->relatedGoal->title, $goal->relatedGoal->slug),
            ),
        );
    }

    private function form(Goal $goal): GoalFormData
    {
        return new GoalFormData(
            $goal->slug,
            $goal->title,
            $goal->type,
            $goal->color,
            $goal->priority,
            $goal->start_date->toDateString(),
            $goal->due_date->toDateString(),
            $goal->target_per_week,
            $goal->area,
            $goal->related_goal_id,
            $goal->description,
            $goal->done_criteria,
            $goal->status,
            $goal->tasks->isNotEmpty(),
        );
    }

    private function progressData(Goal $goal, CarbonImmutable $today): GoalProgressData
    {
        $pace = $this->progress->pace($goal, $today);

        if ($goal->isHabit()) {
            return new GoalProgressData(
                $pace->progress,
                $pace->expected,
                $this->progress->hitDayCount($goal),
                $this->progress->plannedDayCount($goal),
                'days',
                null,
                $this->progress->week($goal, $today),
            );
        }

        $counts = [];

        foreach (TaskStatus::cases() as $status) {
            $counts[$status->value] = $goal->tasks->where('status', $status)->count();
        }

        return new GoalProgressData(
            $pace->progress,
            $pace->expected,
            $counts[TaskStatus::Done->value],
            $goal->tasks->count(),
            'tasks',
            $counts,
            null,
        );
    }

    /**
     * @return list<HabitActionData>
     */
    private function habitActions(Goal $goal, CarbonImmutable $today): array
    {
        return $goal->tasks
            ->filter(fn (Task $task): bool => $task->isRecurring())
            ->map(fn (Task $task): HabitActionData => new HabitActionData(
                $task->id,
                $task->title,
                $task->days_of_week ?? [],
                $task->scheduled_time === null ? 'all day' : $this->labels->time($task->scheduled_time),
                $task->points,
                $this->weekLabel($task, $today),
                $this->sheets->build($task),
            ))
            ->values()
            ->all();
    }

    private function weekLabel(Task $task, CarbonImmutable $today): string
    {
        $monday = $today->startOfWeek();
        $hits = $task->completions
            ->filter(fn (Completion $completion): bool => $completion->completed_on->betweenIncluded($monday, $today))
            ->count();

        return $hits.'/'.count($task->days_of_week ?? []).' this week';
    }

    private function reminder(Reminder $reminder): GoalReminderData
    {
        return new GoalReminderData(
            $reminder->id,
            $this->labels->reminderFrequency($reminder),
            $reminder->label,
            $reminder->isOnce() ? 'once' : $this->labels->time($reminder->time),
            $reminder->channels->map(fn (ReminderChannel $channel): string => $channel->value)->values()->all(),
            $this->reminderForms->build($reminder),
        );
    }

    /**
     * @return list<LogEntryData>
     */
    private function history(Goal $goal, CarbonImmutable $today): array
    {
        return $this->logEntries->entries(
            $goal->activities()->latest('occurred_at')->latest('id')->limit(self::HISTORY_ENTRIES)->get(),
            $goal->user->timezone,
            $today,
        );
    }
}
