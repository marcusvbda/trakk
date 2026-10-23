<?php

declare(strict_types=1);

namespace App\Services\Scoring;

use App\Data\DayScore;
use App\Models\User;
use Carbon\CarbonImmutable;

final class DayPointsService
{
    public function __construct(private readonly ScoreService $scores) {}

    public function for(User $user, CarbonImmutable $day): DayScore
    {
        $completions = $user->completions()->where('completed_on', $day->toDateString())->get();

        return $this->scores->dayScore($completions, $day, $user->daily_points_target);
    }
}
