<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\ReplanTarget;
use Carbon\CarbonImmutable;

final readonly class ReplanData
{
    public function __construct(
        public ReplanTarget $target,
        public ?CarbonImmutable $date = null,
    ) {}
}
