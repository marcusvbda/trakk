<?php

declare(strict_types=1);

namespace App\Enums;

enum PaceStatus: string
{
    case Behind = 'behind';
    case Watch = 'watch';
    case OnPace = 'on_pace';
    case Ahead = 'ahead';

    public function isOnPace(): bool
    {
        return $this === self::OnPace || $this === self::Ahead;
    }
}
