<?php

declare(strict_types=1);

namespace App\Data;

use App\Data\Goals\GoalOptionData;

/**
 * What the create and edit sheets need on every page: the owner's goals for the selects and the date defaults.
 */
final readonly class SheetOptionsData
{
    /**
     * @param  list<GoalOptionData>  $goals
     */
    public function __construct(
        public string $today,
        public string $defaultDueDate,
        public array $goals,
    ) {}
}
