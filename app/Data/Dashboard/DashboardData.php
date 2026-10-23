<?php

declare(strict_types=1);

namespace App\Data\Dashboard;

use App\Data\Consistency;

final readonly class DashboardData
{
    /**
     * @param  list<KpiData>  $kpis
     * @param  list<TodayActionData>  $actions
     * @param  list<OverdueItemData>  $overdue
     * @param  list<UpcomingItemData>  $upcoming
     * @param  list<GoalSummaryData>  $goals
     * @param  list<ReminderItemData>  $reminders
     * @param  list<LogEntryData>  $log
     */
    public function __construct(
        public int $dailyTarget,
        public int $pointsToday,
        public array $kpis,
        public array $actions,
        public array $overdue,
        public ?string $suggestion,
        public array $upcoming,
        public GoalsSummaryData $goalsSummary,
        public array $goals,
        public PointsChartData $pointsChart,
        public Consistency $consistency,
        public array $reminders,
        public ?FocusProjectData $focusProject,
        public array $log,
        public StatusLineData $status,
        public ?FirstRunData $firstRun,
        public EmptyHintsData $hints,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toProps(): array
    {
        return get_object_vars($this);
    }
}
