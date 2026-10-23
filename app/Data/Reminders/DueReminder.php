<?php

declare(strict_types=1);

namespace App\Data\Reminders;

use App\Models\Reminder;
use Carbon\CarbonImmutable;

final readonly class DueReminder
{
    public function __construct(
        public Reminder $reminder,
        public CarbonImmutable $slot,
    ) {}
}
