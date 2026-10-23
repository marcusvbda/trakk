<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\ReminderKind;

/**
 * The raw values the reminder sheet edits, so opening it costs no request.
 */
final readonly class ReminderFormData
{
    /**
     * @param  list<int>|null  $daysOfWeek  ISO weekdays; null for a once-only reminder
     * @param  list<string>  $channels
     */
    public function __construct(
        public int $id,
        public ReminderKind $kind,
        public string $label,
        public string $time,
        public ?array $daysOfWeek,
        public ?string $fireOn,
        public ?int $goalId,
        public array $channels,
        public bool $isActive,
    ) {}
}
