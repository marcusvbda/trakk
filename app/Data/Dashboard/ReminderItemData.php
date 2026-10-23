<?php

declare(strict_types=1);

namespace App\Data\Dashboard;

final readonly class ReminderItemData
{
    public function __construct(
        public int $id,
        public string $timeLabel,
        public string $label,
        public string $frequencyLabel,
        public string $channelsLabel,
        public bool $isActive,
    ) {}
}
