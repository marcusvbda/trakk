<?php

declare(strict_types=1);

namespace App\Data\Goals;

use App\Enums\GoalStatus;

final readonly class GoalListGroupData
{
    /**
     * @param  list<GoalListItemData>  $goals
     */
    public function __construct(
        public GoalStatus $status,
        public array $goals,
    ) {}
}
