<?php

declare(strict_types=1);

namespace App\Data\Dashboard;

use App\Enums\Tone;

final readonly class KpiData
{
    public function __construct(
        public string $id,
        public string $label,
        public string $value,
        public string $suffix,
        public Tone $valueTone,
        public ?int $progress,
        public Tone $progressTone,
        public string $sub,
        public Tone $subTone,
    ) {}
}
