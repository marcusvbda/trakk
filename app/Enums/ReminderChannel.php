<?php

declare(strict_types=1);

namespace App\Enums;

enum ReminderChannel: string
{
    case Telegram = 'telegram';
    case Email = 'email';
}
