<?php

declare(strict_types=1);

namespace App\Enums;

enum GoalType: string
{
    case Habit = 'habit';
    case Project = 'project';
}
