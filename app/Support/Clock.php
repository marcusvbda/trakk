<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;

final class Clock
{
    public function now(): CarbonImmutable
    {
        return Date::now();
    }

    public function nowFor(User $user): CarbonImmutable
    {
        return $this->now()->setTimezone($user->timezone);
    }

    /**
     * The user's calendar date as midnight in the app timezone, so it compares and subtracts cleanly
     * with `date` columns, which are cast the same way.
     */
    public function today(User $user): CarbonImmutable
    {
        return CarbonImmutable::parse($this->nowFor($user)->toDateString());
    }
}
