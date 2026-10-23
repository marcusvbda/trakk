<?php

declare(strict_types=1);

namespace App\Services\Scoring;

use App\Data\Consistency;
use App\Data\DayScore;
use App\Models\Completion;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class ConsistencyService
{
    public function __construct(private readonly ScoreService $scores) {}

    /**
     * Heat level per day for the last weeks, Monday first, ending with the current week.
     *
     * @param  Collection<int, Completion>  $completions
     */
    public function weeks(Collection $completions, CarbonImmutable $today, int $target, int $weeks = 12): Consistency
    {
        $pointsByDay = $this->scores->pointsByDay($completions);
        $firstMonday = $today->startOfWeek()->subWeeks($weeks - 1);
        $grid = [];
        $goodDays = 0;
        $totalDays = 0;

        for ($week = 0; $week < $weeks; $week++) {
            $row = [];

            for ($weekday = 0; $weekday < 7; $weekday++) {
                $day = $firstMonday->addDays($week * 7 + $weekday);

                if ($day->greaterThan($today)) {
                    $row[] = null;

                    continue;
                }

                $level = (new DayScore($day, $pointsByDay[$day->toDateString()] ?? 0, $target))->heatLevel();
                $row[] = $level;
                $totalDays++;
                $goodDays += $level >= 3 ? 1 : 0;
            }

            $grid[] = $row;
        }

        return new Consistency($grid, $goodDays, $totalDays, $totalDays === 0 ? 0 : (int) round($goodDays / $totalDays * 100));
    }
}
