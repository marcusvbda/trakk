<?php

declare(strict_types=1);

namespace App\Enums;

enum DayState: string
{
    case Done = 'done';
    case Missed = 'missed';
    case Upcoming = 'upcoming';
}
