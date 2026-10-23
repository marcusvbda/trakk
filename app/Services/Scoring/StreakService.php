<?php

declare(strict_types=1);

namespace App\Services\Scoring;

use App\Data\DayScore;
use App\Data\Streak;
use App\Models\Completion;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class StreakService
{
    public function __construct(private readonly ScoreService $scores) {}

    /**
     * Consecutive good days ending today, or yesterday while today is still short of a good day.
     *
     * @param  Collection<int, Completion>  $completions
     */
    public function streak(Collection $completions, CarbonImmutable $today, int $target): Streak
    {
        $record = 0;
        $run = 0;
        $latest = null;

        foreach ($this->goodDays($completions, $today, $target) as $day) {
            $run = $latest?->addDay()->equalTo($day) === true ? $run + 1 : 1;
            $record = max($record, $run);
            $latest = $day;
        }

        $isRunAlive = $latest !== null && $latest->greaterThanOrEqualTo($today->subDay());

        return new Streak($isRunAlive ? $run : 0, $record);
    }

    /**
     * @param  Collection<int, Completion>  $completions
     * @return list<CarbonImmutable> ascending
     */
    private function goodDays(Collection $completions, CarbonImmutable $today, int $target): array
    {
        return collect($this->scores->pointsByDay($completions))
            ->map(fn (int $points, string $date): DayScore => new DayScore(CarbonImmutable::parse($date), $points, $target))
            ->filter(fn (DayScore $score): bool => $score->isGoodDay() && $score->date->lessThanOrEqualTo($today))
            ->map(fn (DayScore $score): CarbonImmutable => $score->date)
            ->sortBy(fn (CarbonImmutable $date): int => $date->getTimestamp())
            ->values()
            ->all();
    }
}
