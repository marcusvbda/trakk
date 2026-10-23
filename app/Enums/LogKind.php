<?php

declare(strict_types=1);

namespace App\Enums;

enum LogKind: string
{
    case Done = 'done';
    case Undone = 'undone';
    case Sent = 'sent';
    case Checkin = 'checkin';
    case Warning = 'warning';
    case Reopened = 'reopened';
}
