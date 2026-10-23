<?php

declare(strict_types=1);

namespace App\Services\Goals;

use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\Goal;
use App\Models\User;
use App\Services\Activity\ActivityLogger;
use Carbon\CarbonImmutable;

final class DeadlineScanService
{
    /** Days before the due date at which an active goal gets a `deadline_near` warning. */
    private const array WARN_AT_DAYS = [14, 3];

    public function __construct(private readonly ActivityLogger $activity) {}

    /**
     * @return int how many warnings were logged
     */
    public function scan(User $user, CarbonImmutable $today): int
    {
        $logged = 0;

        foreach ($user->goals()->active()->get() as $goal) {
            $days = (int) $today->diffInDays($goal->due_date, false);

            if (! in_array($days, self::WARN_AT_DAYS, true) || $this->isLogged($goal, $days)) {
                continue;
            }

            $this->activity->deadlineNear($goal, $days);
            $logged++;
        }

        return $logged;
    }

    private function isLogged(Goal $goal, int $days): bool
    {
        return Activity::query()
            ->where('type', ActivityType::DeadlineNear)
            ->where('goal_id', $goal->id)
            ->where('meta->days', $days)
            ->exists();
    }
}
