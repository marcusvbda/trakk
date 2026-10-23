<?php

declare(strict_types=1);

namespace App\Services\Scoring;

use App\Data\DayScore;
use App\Data\PointsSeries;
use App\Data\WeekScore;
use App\Models\Completion;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class ScoreService
{
    /**
     * @param  Collection<int, Completion>  $completions
     * @return array<string, int> points per `Y-m-d` date
     */
    public function pointsByDay(Collection $completions): array
    {
        return $completions
            ->groupBy(fn (Completion $completion): string => $completion->completed_on->toDateString())
            ->map(fn (Collection $day): int => (int) $day->sum('points'))
            ->all();
    }

    /**
     * @param  Collection<int, Completion>  $completions
     */
    public function dayScore(Collection $completions, CarbonImmutable $day, int $target): DayScore
    {
        return $this->scoreOn($this->pointsByDay($completions), $day, $target);
    }

    /**
     * Monday to today against the same span of the previous week.
     *
     * @param  Collection<int, Completion>  $completions
     */
    public function weekScore(Collection $completions, CarbonImmutable $today): WeekScore
    {
        $pointsByDay = $this->pointsByDay($completions);
        $start = $today->startOfWeek();
        $spanDays = (int) $start->diffInDays($today) + 1;

        $points = $this->sumSpan($pointsByDay, $start, $spanDays);
        $previousPoints = $this->sumSpan($pointsByDay, $start->subWeek(), $spanDays);

        return new WeekScore(
            $points,
            $previousPoints,
            $previousPoints === 0 ? null : (int) round(($points - $previousPoints) / $previousPoints * 100),
        );
    }

    /**
     * @param  Collection<int, Completion>  $completions
     */
    public function series(Collection $completions, CarbonImmutable $today, int $target, int $days = 30): PointsSeries
    {
        $pointsByDay = $this->pointsByDay($completions);
        $scores = array_map(
            fn (int $daysAgo): DayScore => $this->scoreOn($pointsByDay, $today->subDays($daysAgo), $target),
            range($days - 1, 0),
        );
        $points = array_map(fn (DayScore $score): int => $score->points, $scores);

        return new PointsSeries(
            $scores,
            round(array_sum($points) / $days, 1),
            max($points),
            count(array_filter($scores, fn (DayScore $score): bool => $score->isTargetHit())),
        );
    }

    /**
     * @param  array<string, int>  $pointsByDay
     */
    private function scoreOn(array $pointsByDay, CarbonImmutable $day, int $target): DayScore
    {
        return new DayScore($day, $pointsByDay[$day->toDateString()] ?? 0, $target);
    }

    /**
     * @param  array<string, int>  $pointsByDay
     */
    private function sumSpan(array $pointsByDay, CarbonImmutable $start, int $days): int
    {
        $total = 0;

        for ($offset = 0; $offset < $days; $offset++) {
            $total += $pointsByDay[$start->addDays($offset)->toDateString()] ?? 0;
        }

        return $total;
    }
}
