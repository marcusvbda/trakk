<?php

declare(strict_types=1);

namespace App\Data\Goals;

use App\Data\TaskSheetData;

final readonly class HabitActionData
{
    /**
     * @param  list<int>  $days  ISO weekdays the action repeats on
     * @param  list<HabitDayData>  $weekDays  Monday to Sunday of the current week
     */
    public function __construct(
        public int $id,
        public string $title,
        public array $days,
        public string $timeLabel,
        public int $points,
        public string $weekLabel,
        public array $weekDays,
        public TaskSheetData $task,
    ) {}
}
