<?php

declare(strict_types=1);

namespace App\Services\Reminders\Messages;

use App\Data\UserSnapshot;
use App\Models\Reminder;

/**
 * Short plain text, no emoji, one fact per line.
 */
interface ReminderMessage
{
    public function build(Reminder $reminder, UserSnapshot $context): string;
}
