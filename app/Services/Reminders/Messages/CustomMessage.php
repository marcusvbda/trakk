<?php

declare(strict_types=1);

namespace App\Services\Reminders\Messages;

use App\Data\UserSnapshot;
use App\Models\Reminder;

final class CustomMessage implements ReminderMessage
{
    public function build(Reminder $reminder, UserSnapshot $context): string
    {
        return $reminder->label;
    }
}
