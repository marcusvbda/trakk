<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Data\Dashboard\GoalSummaryData;
use App\Models\User;
use App\Services\Activity\ActivityLogger;
use App\Services\Dashboard\DashboardService;
use Carbon\CarbonImmutable;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('The active goals with their ids, type, progress against what is expected today, pace status and due date. Call it to get goal ids for create-task.')]
#[IsReadOnly]
final class ListGoalsTool extends TrakkTool
{
    protected function run(Request $request, User $user, CarbonImmutable $today): Response
    {
        $goals = app(DashboardService::class)->for($user, $today)->goals;
        app(ActivityLogger::class)->mcpCall($user, 'list-goals');

        if ($goals === []) {
            return Response::text('No active goals.');
        }

        return Response::text(implode("\n", array_map(
            fn (GoalSummaryData $goal): string => sprintf(
                '- [%d] %s · %s · %s priority · %d%% (expected %d%%) · %s %+d%% · due %s (%dd left)',
                $goal->id, $goal->title, $goal->type->value, $goal->priority->value, $goal->percent, $goal->expectedPercent,
                $goal->pace->status->value, $goal->pace->delta, $goal->dueLabel, $goal->daysLeft,
            ),
            $goals,
        )));
    }
}
