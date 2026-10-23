<?php

declare(strict_types=1);

namespace App\Services\Activity;

use App\Enums\ActivitySource;
use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\User;
use App\Services\Scoring\DayPointsService;
use Carbon\CarbonImmutable;

final class CheckInService
{
    public function __construct(
        private readonly DayPointsService $dayPoints,
        private readonly ActivityLogger $activity,
    ) {}

    /** Closes the day: logs the day score against the target. A second check-in the same day updates the first. */
    public function closeDay(User $user, CarbonImmutable $today, ActivitySource $source = ActivitySource::Web): Activity
    {
        return $this->activity->checkIn($user, $this->dayPoints->for($user, $today), $this->existing($user, $today), $source);
    }

    private function existing(User $user, CarbonImmutable $today): ?Activity
    {
        return $user->activities()
            ->where('type', ActivityType::CheckIn)
            ->where('meta->date', $today->toDateString())
            ->first();
    }
}
