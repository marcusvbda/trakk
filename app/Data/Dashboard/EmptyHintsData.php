<?php

declare(strict_types=1);

namespace App\Data\Dashboard;

final readonly class EmptyHintsData
{
    public function __construct(
        public ?string $nextActionDay,
        public ?NextDeadlineData $nextDeadline,
    ) {}
}
