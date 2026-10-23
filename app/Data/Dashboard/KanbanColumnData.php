<?php

declare(strict_types=1);

namespace App\Data\Dashboard;

use App\Enums\TaskStatus;

final readonly class KanbanColumnData
{
    /**
     * @param  list<KanbanCardData>  $cards
     */
    public function __construct(
        public TaskStatus $status,
        public int $hiddenCount,
        public array $cards,
    ) {}
}
