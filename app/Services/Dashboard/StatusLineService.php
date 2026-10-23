<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Data\Dashboard\StatusLineData;
use App\Models\Reminder;
use App\Models\User;
use App\Support\Clock;
use App\Support\Labels;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class StatusLineService
{
    private const int LOOKAHEAD_DAYS = 7;

    public function __construct(
        private readonly Clock $clock,
        private readonly Labels $labels,
        private readonly McpStatusService $mcp,
    ) {}

    /**
     * @param  Collection<int, Reminder>  $reminders  all of the owner's reminders
     */
    public function for(User $user, Collection $reminders, CarbonImmutable $today): StatusLineData
    {
        $isLinked = $user->telegram_chat_id !== null && $user->telegram_chat_id !== '';
        $hasToken = (string) config('services.telegram.bot_token') !== '';

        return new StatusLineData(
            $this->mcp->toolCount(),
            $this->nextReminderAt($reminders, $this->clock->nowFor($user), $today),
            $isLinked,
            $isLinked && $hasToken && $reminders->every(fn (Reminder $reminder): bool => $reminder->last_error === null),
        );
    }

    /**
     * The next active reminder still to come today, else the first one on the next days (up to a week ahead,
     * so a weekly reminder is still found). Today and tomorrow show `HH:MM`, later days the weekday as well.
     *
     * @param  Collection<int, Reminder>  $reminders
     */
    private function nextReminderAt(Collection $reminders, CarbonImmutable $now, CarbonImmutable $today): ?string
    {
        $active = $reminders->filter(fn (Reminder $reminder): bool => $reminder->is_active);
        $currentTime = $now->format('H:i');

        $later = $this->timesOn($active, $today)->filter(fn (string $time): bool => $time > $currentTime)->first();

        if ($later !== null) {
            return $later;
        }

        for ($offset = 1; $offset <= self::LOOKAHEAD_DAYS; $offset++) {
            $day = $today->addDays($offset);
            $first = $this->timesOn($active, $day)->first();

            if ($first !== null) {
                return $offset === 1 ? $first : strtolower($day->format('D')).' '.$first;
            }
        }

        return null;
    }

    /**
     * @param  Collection<int, Reminder>  $reminders
     * @return Collection<int, string> `HH:MM`, earliest first
     */
    private function timesOn(Collection $reminders, CarbonImmutable $day): Collection
    {
        return $reminders
            ->filter(fn (Reminder $reminder): bool => $reminder->isOnce()
                ? $reminder->fire_on?->equalTo($day) === true
                : in_array($day->dayOfWeekIso, $reminder->days_of_week ?? [], true))
            ->map(fn (Reminder $reminder): string => $this->labels->time($reminder->time))
            ->sort()
            ->values();
    }
}
