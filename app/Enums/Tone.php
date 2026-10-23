<?php

declare(strict_types=1);

namespace App\Enums;

enum Tone: string
{
    case Default = 'default';
    case Accent = 'accent';
    case Danger = 'danger';
    case Warning = 'warning';
    case Info = 'info';
    case Muted = 'muted';
    case Dim = 'dim';
}
