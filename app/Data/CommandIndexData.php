<?php

declare(strict_types=1);

namespace App\Data;

use App\Data\Dashboard\TodayActionData;
use App\Data\Goals\GoalOptionData;

/**
 * What the command palette searches besides its commands.
 */
final readonly class CommandIndexData
{
    /**
     * @param  list<GoalOptionData>  $goals
     * @param  list<TodayActionData>  $tasks  today's actions, so selecting one can check it
     */
    public function __construct(
        public array $goals,
        public array $tasks,
    ) {}
}
