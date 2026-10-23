<?php

declare(strict_types=1);

namespace App\Data\Dashboard;

use App\Enums\LogKind;

final readonly class LogEntryData
{
    public function __construct(
        public int $id,
        public string $timeLabel,
        public LogKind $kind,
        public string $text,
        public ?string $trailing,
    ) {}
}
