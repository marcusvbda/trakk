<?php

declare(strict_types=1);

namespace App\Data\Goals;

use App\Data\Dashboard\LogEntryData;

final readonly class GoalDetailData
{
    /**
     * @param  list<HabitActionData>|null  $actions  habit goals; `board` is null for them
     * @param  list<GoalReminderData>  $reminders
     * @param  list<LogEntryData>  $history
     */
    public function __construct(
        public GoalOverviewData $goal,
        public GoalFormData $form,
        public GoalProgressData $progress,
        public PaceChartData $paceChart,
        public ?GoalBoardData $board,
        public ?array $actions,
        public array $reminders,
        public array $history,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toProps(): array
    {
        return get_object_vars($this);
    }
}
