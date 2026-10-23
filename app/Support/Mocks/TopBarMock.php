<?php

declare(strict_types=1);

namespace App\Support\Mocks;

/**
 * Prototype data for the top bar shown on every authenticated page. Replaced by a real clock/calendar read in M3.
 */
final class TopBarMock
{
    /**
     * @return array{dateLabel: string, yearProgress: int}
     */
    public static function props(): array
    {
        return [
            'dateLabel' => 'fri, 23 oct 2026 · wk 43 · day 295/365',
            'yearProgress' => 81,
        ];
    }
}
