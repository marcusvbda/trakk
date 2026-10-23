<?php

declare(strict_types=1);

namespace App\Enums;

enum GoalColor: string
{
    case Amber = 'amber';
    case Blue = 'blue';
    case Cyan = 'cyan';
    case Pink = 'pink';
    case Violet = 'violet';
    case Sand = 'sand';
    case Green = 'green';

    /** Same values as the `--color-goal-*` tokens in `resources/css/app.css`. */
    public function hex(): string
    {
        return match ($this) {
            self::Amber => '#F2B03D',
            self::Blue => '#6CB4FF',
            self::Cyan => '#4FD1D9',
            self::Pink => '#F28CB1',
            self::Violet => '#B49BFF',
            self::Sand => '#D9D4C7',
            self::Green => '#4ADE9A',
        };
    }
}
