<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\ReminderChannel;
use App\Enums\ReminderKind;
use Carbon\CarbonImmutable;

final readonly class ReminderData
{
    /**
     * @param  list<ReminderChannel>  $channels
     * @param  list<int>|null  $daysOfWeek  ISO weekdays for a recurring reminder; null when `$fireOn` is set
     */
    public function __construct(
        public ReminderKind $kind,
        public string $label,
        public string $time,
        public array $channels,
        public ?array $daysOfWeek = null,
        public ?CarbonImmutable $fireOn = null,
        public ?int $goalId = null,
        public bool $isActive = true,
    ) {}
}
