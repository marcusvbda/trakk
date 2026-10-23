<?php

declare(strict_types=1);

namespace App\Services\Reminders;

use App\Data\ReminderData;
use App\Enums\ActivitySource;
use App\Enums\ReminderChannel;
use App\Enums\ReminderKind;
use App\Models\Reminder;
use App\Models\User;
use App\Services\Activity\ActivityLogger;
use InvalidArgumentException;

final class ReminderService
{
    private const string TIME_PATTERN = '/^([01]\d|2[0-3]):[0-5]\d$/';

    private const array EVERY_DAY = [1, 2, 3, 4, 5, 6, 7];

    public function __construct(private readonly ActivityLogger $activity) {}

    public function create(User $user, ReminderData $data): Reminder
    {
        $this->assertValid($user, $data);

        return $user->reminders()->create($this->attributes($data));
    }

    public function update(Reminder $reminder, ReminderData $data): Reminder
    {
        $this->assertValid($reminder->loadMissing('user')->user, $data);

        $reminder->update($this->attributes($data));

        return $reminder;
    }

    public function delete(Reminder $reminder): void
    {
        $reminder->delete();
    }

    public function setActive(Reminder $reminder, bool $isActive, ActivitySource $source = ActivitySource::Web): Reminder
    {
        if ($reminder->is_active === $isActive) {
            return $reminder;
        }

        $reminder->update(['is_active' => $isActive]);

        $isActive
            ? $this->activity->reminderEnabled($reminder, $source)
            : $this->activity->reminderPaused($reminder, $source);

        return $reminder;
    }

    /** Created once, when the user has no reminders yet. */
    public function createDefaults(User $user): void
    {
        if ($user->reminders()->exists()) {
            return;
        }

        foreach ($this->defaults() as $defaults) {
            $this->create($user, $defaults);
        }
    }

    /**
     * @return list<ReminderData>
     */
    private function defaults(): array
    {
        return [
            new ReminderData(ReminderKind::DailyBrief, 'Daily brief', '07:30', [ReminderChannel::Telegram], self::EVERY_DAY),
            new ReminderData(ReminderKind::EveningCheckin, 'Evening check-in', '21:30', [ReminderChannel::Telegram], self::EVERY_DAY),
            new ReminderData(ReminderKind::DueTomorrow, 'Tasks due tomorrow', '18:00', [ReminderChannel::Telegram], self::EVERY_DAY),
            new ReminderData(ReminderKind::WeeklyReview, 'Weekly review', '10:00', [ReminderChannel::Telegram, ReminderChannel::Email], [7]),
            new ReminderData(ReminderKind::OffPace, 'Goals off pace', '09:00', [ReminderChannel::Email], [1], isActive: false),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(ReminderData $data): array
    {
        $days = $data->daysOfWeek === null || $data->daysOfWeek === [] ? null : array_values(array_unique($data->daysOfWeek));

        if ($days !== null) {
            sort($days);
        }

        return [
            'goal_id' => $data->goalId,
            'kind' => $data->kind,
            'label' => $data->label,
            'time' => $data->time,
            'days_of_week' => $days,
            'fire_on' => $data->fireOn,
            'channels' => array_values(array_unique($data->channels, SORT_REGULAR)),
            'is_active' => $data->isActive,
        ];
    }

    private function assertValid(User $user, ReminderData $data): void
    {
        if (preg_match(self::TIME_PATTERN, $data->time) !== 1) {
            throw new InvalidArgumentException('The time must be HH:MM, from 00:00 to 23:59.');
        }

        if ($data->channels === []) {
            throw new InvalidArgumentException('A reminder needs at least one channel.');
        }

        if (($data->fireOn === null) === ($data->daysOfWeek === null || $data->daysOfWeek === [])) {
            throw new InvalidArgumentException('A reminder is either recurring (weekdays) or once (a date).');
        }

        foreach ($data->daysOfWeek ?? [] as $day) {
            if ($day < 1 || $day > 7) {
                throw new InvalidArgumentException('Weekdays are ISO numbers from 1 to 7.');
            }
        }

        if ($data->kind->isGoalScoped() && $data->goalId === null) {
            throw new InvalidArgumentException('This kind of reminder belongs to a goal.');
        }

        if ($data->goalId !== null && ! $user->goals()->whereKey($data->goalId)->exists()) {
            throw new InvalidArgumentException('The goal must belong to the same user.');
        }
    }
}
