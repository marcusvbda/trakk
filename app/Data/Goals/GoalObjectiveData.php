<?php

declare(strict_types=1);

namespace App\Data\Goals;

final readonly class GoalObjectiveData
{
    public function __construct(
        public ?string $text,
        public ?string $doneWhen,
        public ?string $area,
        public ?GoalLinkData $linkedTo,
    ) {}
}
