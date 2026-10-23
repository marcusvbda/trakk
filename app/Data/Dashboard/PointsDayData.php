<?php

declare(strict_types=1);

namespace App\Data\Dashboard;

use App\Enums\PointsTier;

final readonly class PointsDayData
{
    public function __construct(
        public int $points,
        public PointsTier $tier,
        public string $label,
    ) {}
}
