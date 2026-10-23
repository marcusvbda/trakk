<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\TaskStatus;
use Carbon\CarbonImmutable;

final readonly class TaskData
{
    /**
     * @param  TaskStatus|null  $status  todo on create, unchanged on update when null
     * @param  list<int>|null  $daysOfWeek  ISO weekdays, 1 = Monday; set for habit actions, null for one-off tasks
     */
    public function __construct(
        public string $title,
        public int $points,
        public ?TaskStatus $status = null,
        public ?CarbonImmutable $dueDate = null,
        public ?string $scheduledTime = null,
        public ?array $daysOfWeek = null,
        public ?string $tag = null,
        public ?string $notes = null,
    ) {}
}
