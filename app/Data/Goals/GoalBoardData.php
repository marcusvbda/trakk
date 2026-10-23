<?php

declare(strict_types=1);

namespace App\Data\Goals;

use App\Data\Dashboard\KanbanColumnData;

final readonly class GoalBoardData
{
    /**
     * @param  list<KanbanColumnData>  $columns
     */
    public function __construct(public array $columns) {}
}
