<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Data\Dashboard\OverdueItemData;
use App\Data\Dashboard\TodayActionData;
use App\Data\Dashboard\UpcomingItemData;
use App\Models\User;
use App\Services\Activity\ActivityLogger;
use App\Services\Dashboard\DashboardService;
use Carbon\CarbonImmutable;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description("Today's actions with their ids and points, the day's points against the target, overdue tasks and what is due in the next 7 days. Call it first to see what can be completed and to get task ids.")]
#[IsReadOnly]
final class GetTodayTool extends TrakkTool
{
    protected function run(Request $request, User $user, CarbonImmutable $today): Response
    {
        $dashboard = app(DashboardService::class)->for($user, $today);
        app(ActivityLogger::class)->mcpCall($user, 'get-today');

        $lines = [
            strtolower($today->format('D, d M Y')).' · '.$dashboard->pointsToday.'/'.$dashboard->dailyTarget.' pts',
            $dashboard->actions === [] ? 'today: nothing scheduled' : 'today:',
            ...array_map(fn (TodayActionData $action): string => sprintf('- [%d] %s%s · %s · +%d%s', $action->id, $action->isDone ? '(done) ' : '', $action->title, $action->timeLabel, $action->points, $action->daysLate > 0 && ! $action->isDone ? " · {$action->daysLate}d late" : ''), $dashboard->actions),
        ];

        if ($dashboard->overdue !== []) {
            $lines[] = 'overdue:';
            array_push($lines, ...array_map(fn (OverdueItemData $item): string => "- [{$item->id}] {$item->title} · {$item->goalName} · due {$item->dueLabel} · {$item->daysLate}d late", $dashboard->overdue));
        }

        if ($dashboard->upcoming !== []) {
            $lines[] = 'next 7 days:';
            array_push($lines, ...array_map(fn (UpcomingItemData $item): string => "- {$item->dayLabel} · {$item->title} · {$item->goalName}", $dashboard->upcoming));
        }

        return Response::text(implode("\n", $lines));
    }
}
