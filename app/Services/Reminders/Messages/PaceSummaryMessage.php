<?php

declare(strict_types=1);

namespace App\Services\Reminders\Messages;

use App\Data\UserSnapshot;
use App\Models\Goal;
use App\Models\Reminder;
use App\Services\Goals\GoalProgressService;
use App\Support\Labels;

final class PaceSummaryMessage implements ReminderMessage
{
    public function __construct(
        private readonly GoalProgressService $progress,
        private readonly Labels $labels,
    ) {}

    public function build(Reminder $reminder, UserSnapshot $context): string
    {
        /** @var Goal|null $goal */
        $goal = $context->goals->firstWhere('id', $reminder->goal_id);

        if ($goal === null) {
            return $reminder->label.' · the goal is not active';
        }

        $pace = $this->progress->pace($goal, $context->today);
        $unit = $this->progress->paceUnit($goal);

        return implode("\n", [
            $this->labels->goalName($goal).' · pace',
            sprintf('progress %d%% · expected %d%% · %s %+d%%', $pace->progress, $pace->expected, $pace->status->value, $pace->delta),
            "current {$this->progress->currentPace($goal, $context->today)} {$unit} · required {$this->progress->requiredPace($goal, $context->today)} {$unit}",
            'projected '.$this->progress->projection($goal, $context->today).'% at the due date',
        ]);
    }
}
