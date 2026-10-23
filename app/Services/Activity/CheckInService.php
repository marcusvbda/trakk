<?php

declare(strict_types=1);

namespace App\Services\Activity;

use App\Data\DayScore;
use App\Enums\ActivitySource;
use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\User;
use App\Services\Scoring\ScoreService;
use Carbon\CarbonImmutable;

final class CheckInService
{
    public function __construct(
        private readonly ScoreService $scores,
        private readonly ActivityLogger $activity,
    ) {}

    /** Closes the day: logs the day score against the target. A second check-in the same day updates the first. */
    public function closeDay(User $user, CarbonImmutable $today, ActivitySource $source = ActivitySource::Web): Activity
    {
        return $this->activity->checkIn($user, $this->scoreOf($user, $today), $this->existing($user, $today), $source);
    }

    private function scoreOf(User $user, CarbonImmutable $today): DayScore
    {
        $completions = $user->completions()->where('completed_on', $today->toDateString())->get();

        return $this->scores->dayScore($completions, $today, $user->daily_points_target);
    }

    private function existing(User $user, CarbonImmutable $today): ?Activity
    {
        return $user->activities()
            ->where('type', ActivityType::CheckIn)
            ->where('meta->date', $today->toDateString())
            ->first();
    }
}
