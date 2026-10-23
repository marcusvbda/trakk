<?php

declare(strict_types=1);

namespace App\Data;

final readonly class ChecklistItemData
{
    public function __construct(
        public int $id,
        public string $title,
        public bool $isDone,
    ) {}
}
