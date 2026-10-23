<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Mcp\Tools\CompleteTaskTool;
use App\Mcp\Tools\CreateGoalTool;
use App\Mcp\Tools\CreateTaskTool;
use App\Mcp\Tools\GetTodayTool;
use App\Mcp\Tools\ListGoalsTool;
use App\Mcp\Tools\MoveTaskTool;
use App\Mcp\Tools\ReplanTasksTool;
use App\Mcp\Tools\UncompleteTaskTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Trakk')]
#[Version('0.1.0')]
#[Instructions(<<<'TEXT'
Trakk is the owner's personal goal tracker: goals (habits that repeat on weekdays and projects with a deadline), tasks
worth 1 to 5 points, and a daily points target. Use it to read how the day and the goals are going and to log progress.

Start with get-today to see today's actions and ids, or list-goals for goal ids and pace. To log something the owner
did ("I did 30 minutes of speaking") call complete-task with the title, or the id from get-today; undo a mistake with
uncomplete-task. Use move-task for project tasks (todo, doing, done), create-task to add a task to a goal,
create-goal for a new goal and replan-tasks to push overdue tasks to a new date.

If a title matches several tasks the tool answers with their ids: ask the owner which one, then call again with task_id.
Everything you do is logged in Trakk with a "claude" mark.
TEXT)]
final class TrakkServer extends Server
{
    /** Also the tool count shown in the top bar. */
    public const array TOOLS = [
        GetTodayTool::class,
        ListGoalsTool::class,
        CompleteTaskTool::class,
        UncompleteTaskTool::class,
        CreateTaskTool::class,
        MoveTaskTool::class,
        CreateGoalTool::class,
        ReplanTasksTool::class,
    ];

    protected array $tools = self::TOOLS;
}
