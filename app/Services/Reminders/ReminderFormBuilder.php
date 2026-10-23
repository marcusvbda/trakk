<?php

declare(strict_types=1);

namespace App\Services\Reminders;

use App\Data\ReminderFormData;
use App\Enums\ReminderChannel;
use App\Models\Reminder;
use App\Support\Labels;

final class ReminderFormBuilder
{
    public function __construct(private readonly Labels $labels) {}

    public function build(Reminder $reminder): ReminderFormData
    {
        return new ReminderFormData(
            $reminder->id,
            $reminder->kind,
            $reminder->label,
            $this->labels->time($reminder->time),
            $reminder->days_of_week,
            $reminder->fire_on?->toDateString(),
            $reminder->goal_id,
            $reminder->channels->map(fn (ReminderChannel $channel): string => $channel->value)->values()->all(),
            $reminder->is_active,
        );
    }
}
