<?php

declare(strict_types=1);

namespace App\Data\Dashboard;

use App\Enums\GoalColor;

final readonly class FocusProjectData
{
    /**
     * @param  list<KanbanColumnData>  $columns
     */
    public function __construct(
        public string $goalSlug,
        public string $title,
        public GoalColor $color,
        public int $doneCount,
        public int $totalCount,
        public int $percent,
        public array $columns,
    ) {}
}
