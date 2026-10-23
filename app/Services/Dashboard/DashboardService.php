<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Data\Dashboard\DashboardData;
use App\Data\Dashboard\FirstRunData;
use App\Data\Dashboard\GoalsSummaryData;
use App\Data\Dashboard\GoalSummaryData;
use App\Data\Dashboard\LogEntryData;
use App\Data\Dashboard\PaceData;
use App\Data\Dashboard\PointsChartData;
use App\Data\Dashboard\PointsDayData;
use App\Data\Dashboard\ReminderItemData;
use App\Data\DayScore;
use App\Enums\ActivityType;
use App\Enums\TaskStatus;
use App\Models\Activity;
use App\Models\Completion;
use App\Models\Goal;
use App\Models\Reminder;
use App\Models\Task;
use App\Models\User;
use App\Services\Goals\GoalProgressService;
use App\Services\Scoring\ConsistencyService;
use App\Services\Scoring\ScoreService;
use App\Services\Scoring\StreakService;
use App\Services\Tasks\OnTimeService;
use App\Support\Labels;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Builds every dashboard panel from a fixed set of queries: active goals with their tasks and completions, the
 * owner's completions, reminders, the latest activities and the focus project's checklist counts.
 */
final class DashboardService
{
    private const int LOG_ENTRIES = 8;

    private const int CHART_DAYS = 30;

    private const int CHART_MIN_SCALE = 20;

    public function __construct(
        private readonly GoalProgressService $progress,
        private readonly ScoreService $scores,
        private readonly StreakService $streaks,
        private readonly ConsistencyService $consistency,
        private readonly OnTimeService $onTime,
        private readonly KpiService $kpis,
        private readonly TodayListBuilder $todayList,
        private readonly AgendaBuilder $agenda,
        private readonly SuggestionService $suggestions,
        private readonly FocusProjectService $focusProject,
        private readonly StatusLineService $statusLine,
        private readonly Labels $labels,
    ) {}

    public function for(User $user, CarbonImmutable $today): DashboardData
    {
        $goals = $this->activeGoals($user);
        $tasks = $goals->flatMap(fn (Goal $goal): Collection => $goal->tasks)->values();
        $completions = $user->completions()->get();
        $reminders = $user->reminders()->orderByRaw('goal_id is not null')->orderBy('id')->get();
        $target = $user->daily_points_target;
        $day = $this->scores->dayScore($completions, $today, $target);

        return new DashboardData(
            $target,
            $day->points,
            $this->kpis->build(
                $day,
                $this->streaks->streak($completions, $today, $target),
                $this->scores->weekScore($completions, $today),
                $goals,
                $this->onTime->rate($tasks, $today, $user->timezone),
                $today,
            ),
            $this->todayList->build($tasks, $today),
            $this->agenda->overdue($tasks, $today),
            $this->suggestions->suggest($goals, $today),
            $this->agenda->upcoming($goals, $tasks, $today),
            $this->goalsSummary($goals, $today),
            $goals->map(fn (Goal $goal): GoalSummaryData => $this->goalSummary($goal, $today))->values()->all(),
            $this->pointsChart($completions, $today, $target),
            $this->consistency->weeks($completions, $today, $target),
            $reminders->map(fn (Reminder $reminder): ReminderItemData => $this->reminderItem($reminder))->values()->all(),
            $this->focusProject->for($goals, $today),
            $this->log($user, $today),
            $this->statusLine->for($user, $reminders, $today),
            $this->firstRun($user, $goals),
            $this->agenda->hints($goals, $tasks, $today),
        );
    }

    /**
     * Shown only to an owner who has never created a goal; a paused or finished goal is still a goal.
     *
     * @param  Collection<int, Goal>  $activeGoals
     */
    private function firstRun(User $user, Collection $activeGoals): ?FirstRunData
    {
        if ($activeGoals->isNotEmpty() || $user->goals()->exists()) {
            return null;
        }

        return new FirstRunData($user->telegram_chat_id !== null && $user->telegram_chat_id !== '');
    }

    /**
     * @return Collection<int, Goal>
     */
    private function activeGoals(User $user): Collection
    {
        $goals = $user->goals()
            ->active()
            ->with(['tasks' => fn ($query) => $query->orderBy('position'), 'tasks.completions'])
            ->orderBy('position')
            ->get();

        $goals->each(fn (Goal $goal) => $goal->tasks->each(fn (Task $task) => $task->setRelation('goal', $goal)));

        return $goals;
    }

    /**
     * @param  Collection<int, Goal>  $goals
     */
    private function goalsSummary(Collection $goals, CarbonImmutable $today): GoalsSummaryData
    {
        return new GoalsSummaryData(
            $goals->count(),
            $goals->filter(fn (Goal $goal): bool => $this->progress->pace($goal, $today)->delta >= 0)->count(),
        );
    }

    private function goalSummary(Goal $goal, CarbonImmutable $today): GoalSummaryData
    {
        $pace = $this->progress->pace($goal, $today);
        $doneCount = $goal->tasks->filter(fn (Task $task): bool => $task->status === TaskStatus::Done)->count();

        return new GoalSummaryData(
            $goal->id,
            $goal->slug,
            $goal->title,
            $goal->type,
            $goal->priority,
            $goal->color,
            $this->labels->shortDate($goal->start_date),
            $this->labels->shortDate($goal->due_date),
            max(0, (int) $today->diffInDays($goal->due_date, false)),
            $this->labels->goalFrequency($goal),
            $pace->progress,
            $pace->expected,
            new PaceData($pace->status, $pace->delta),
            $goal->isHabit() ? $this->progress->week($goal, $today) : null,
            $goal->isProject() ? $doneCount.'/'.$goal->tasks->count().' tasks' : null,
            $goal->isProject(),
        );
    }

    /**
     * @param  Collection<int, Completion>  $completions
     */
    private function pointsChart(Collection $completions, CarbonImmutable $today, int $target): PointsChartData
    {
        $series = $this->scores->series($completions, $today, $target, self::CHART_DAYS);
        $days = $series->days;

        return new PointsChartData(
            $target,
            max(self::CHART_MIN_SCALE, $series->bestDay),
            array_map(fn (DayScore $score): PointsDayData => new PointsDayData(
                $score->points,
                $score->tier(),
                ($score->date->equalTo($today) ? 'today · ' : '').$score->points.' pts',
            ), $days),
            $this->labels->shortDate($days[0]->date),
            $this->labels->shortDate($days[intdiv(self::CHART_DAYS, 2) - 1]->date),
            $series->average,
            $series->bestDay,
            $series->daysAtTarget,
        );
    }

    private function reminderItem(Reminder $reminder): ReminderItemData
    {
        return new ReminderItemData(
            $reminder->id,
            $this->labels->time($reminder->time),
            $reminder->label,
            $this->labels->reminderFrequency($reminder),
            $this->labels->reminderChannels($reminder),
            $reminder->is_active,
        );
    }

    /**
     * @return list<LogEntryData>
     */
    private function log(User $user, CarbonImmutable $today): array
    {
        return $user->activities()
            ->latest('occurred_at')
            ->latest('id')
            ->limit(self::LOG_ENTRIES)
            ->get()
            ->map(fn (Activity $activity): LogEntryData => new LogEntryData(
                $activity->id,
                $this->labels->logTime($activity->occurred_at, $user->timezone, $today),
                $activity->type->logKind(),
                $activity->message,
                $this->trailing($activity),
            ))
            ->values()
            ->all();
    }

    private function trailing(Activity $activity): ?string
    {
        return match ($activity->type) {
            ActivityType::TaskCompleted => $activity->points === null ? null : '+'.$activity->points,
            ActivityType::CheckIn => ($activity->meta['score'] ?? '?').'/'.($activity->meta['target'] ?? '?'),
            default => null,
        };
    }
}
