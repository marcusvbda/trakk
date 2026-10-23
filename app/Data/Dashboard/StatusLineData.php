<?php

declare(strict_types=1);

namespace App\Data\Dashboard;

final readonly class StatusLineData
{
    public function __construct(
        public int $mcpTools,
        public ?string $nextReminderAt,
        public bool $isTelegramLinked,
        public bool $isTelegramActive,
    ) {}
}
