<?php

declare(strict_types=1);

namespace App\Enums;

enum ReplanTarget: string
{
    case Today = 'today';
    case Tomorrow = 'tomorrow';
    case NextMonday = 'next_monday';
    case Date = 'date';
}
