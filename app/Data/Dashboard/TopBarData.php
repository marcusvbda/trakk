<?php

declare(strict_types=1);

namespace App\Data\Dashboard;

final readonly class TopBarData
{
    public function __construct(
        public string $dateLabel,
        public int $yearProgress,
        public int $mcpTools,
        public bool $isMcpLive,
    ) {}
}
