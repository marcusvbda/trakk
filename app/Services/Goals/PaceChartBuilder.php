<?php

declare(strict_types=1);

namespace App\Services\Goals;

use App\Data\Goals\PaceChartData;
use App\Data\Goals\PaceChartPointData;
use App\Data\Goals\PaceChartTickData;
use App\Models\Goal;
use App\Support\Labels;
use Carbon\CarbonImmutable;

/**
 * Expects `$goal->tasks` and `tasks.completions` loaded.
 */
final class PaceChartBuilder
{
    private const int SAMPLES = 5;

    /** Month ticks closer than this share of the window to the previous tick, the start or the due date are dropped. */
    private const float MIN_TICK_GAP = 0.18;

    public function __construct(
        private readonly GoalProgressService $progress,
        private readonly Labels $labels,
    ) {}

    public function build(Goal $goal, CarbonImmutable $today): PaceChartData
    {
        $totalDays = $this->progress->totalDays($goal);
        $elapsedDays = $this->progress->elapsedDays($goal, $today);

        return new PaceChartData(
            $totalDays,
            $elapsedDays,
            $this->labels->shortDate($goal->start_date),
            $this->labels->shortDate($goal->due_date),
            $this->ticks($goal, $totalDays),
            $this->actual($goal, $elapsedDays),
            $this->progress->expected($goal, $today),
            $this->progress->projection($goal, $today),
            $this->progress->currentPace($goal, $today),
            $this->progress->requiredPace($goal, $today),
            $goal->isHabit() ? 'days/wk' : 'tasks/wk',
            $this->caption($goal, $today),
        );
    }

    /**
     * Evenly spaced days from the start to today, both included; the last one is today's real progress.
     *
     * @return list<PaceChartPointData>
     */
    private function actual(Goal $goal, int $elapsedDays): array
    {
        $days = array_values(array_unique(array_map(
            fn (int $sample): int => (int) round($sample * $elapsedDays / (self::SAMPLES - 1)),
            range(0, self::SAMPLES - 1),
        )));

        return array_map(fn (int $day): PaceChartPointData => new PaceChartPointData(
            $day,
            $day === $elapsedDays
                ? $this->progress->progress($goal)
                : $this->progress->progressOn($goal, $goal->start_date->addDays($day)),
        ), $days);
    }

    /**
     * @return list<PaceChartTickData>
     */
    private function ticks(Goal $goal, int $totalDays): array
    {
        $ticks = [];
        $previousDay = 0;

        for ($month = $goal->start_date->startOfMonth()->addMonth(); $month->lessThan($goal->due_date); $month = $month->addMonth()) {
            $day = (int) $goal->start_date->diffInDays($month);

            if (($day - $previousDay) / $totalDays < self::MIN_TICK_GAP || ($totalDays - $day) / $totalDays < self::MIN_TICK_GAP) {
                continue;
            }

            $ticks[] = new PaceChartTickData($day, strtolower($month->format('M')));
            $previousDay = $day;
        }

        return $ticks;
    }

    private function caption(Goal $goal, CarbonImmutable $today): ?string
    {
        if ($today->lessThan($goal->start_date)) {
            $days = (int) $today->diffInDays($goal->start_date);

            return 'starts in '.$days.($days === 1 ? ' day' : ' days');
        }

        return $goal->isProject() && $goal->tasks->isEmpty() ? 'add tasks to measure progress' : null;
    }
}
