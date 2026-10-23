<?php

declare(strict_types=1);

namespace App\Enums;

enum PointsTier: string
{
    case Target = 'target';
    case Good = 'good';
    case Low = 'low';
}
