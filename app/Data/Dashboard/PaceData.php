<?php

declare(strict_types=1);

namespace App\Data\Dashboard;

use App\Enums\PaceStatus;

final readonly class PaceData
{
    public function __construct(
        public PaceStatus $status,
        public int $delta,
    ) {}
}
