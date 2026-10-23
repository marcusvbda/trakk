<?php

declare(strict_types=1);

namespace App\Data\Dashboard;

final readonly class KanbanCardData
{
    public function __construct(
        public int $id,
        public string $title,
        public ?string $dueLabel,
        public int $checklistDone,
        public int $checklistTotal,
        public bool $isOverdue,
        public ?string $tag,
    ) {}
}
