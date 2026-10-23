<?php

declare(strict_types=1);

namespace App\Services\Reminders\Messages;

use App\Data\UserSnapshot;
use App\Models\Goal;
use App\Models\Reminder;
use App\Services\Goals\GoalProgressService;
use App\Support\Labels;

final class OffPaceMessage implements ReminderMessage
{
    public function __construct(
        private readonly GoalProgressService $progress,
        private readonly Labels $labels,
    ) {}

    public function build(Reminder $reminder, UserSnapshot $context): string
    {
        $behind = $context->goals
            ->map(fn (Goal $goal): array => [$goal, $this->progress->pace($goal, $context->today)])
            ->filter(fn (array $entry): bool => $entry[1]->delta < 0)
            ->sortBy(fn (array $entry): int => $entry[1]->delta);

        return implode("\n", [
            'goals off pace',
            ...($behind->isEmpty()
                ? ['every goal is on pace']
                : $behind->map(fn (array $entry): string => sprintf('· %s · %s %+d%%', $this->labels->goalName($entry[0]), $entry[1]->status->value, $entry[1]->delta))->values()->all()),
        ]);
    }
}
