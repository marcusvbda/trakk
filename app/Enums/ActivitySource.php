<?php

declare(strict_types=1);

namespace App\Enums;

enum ActivitySource: string
{
    case Web = 'web';
    case Mcp = 'mcp';
    case System = 'system';
}
