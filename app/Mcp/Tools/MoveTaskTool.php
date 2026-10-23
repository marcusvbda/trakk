<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\ActivitySource;
use App\Enums\TaskStatus;
use App\Models\User;
use App\Services\Scoring\DayPointsService;
use App\Services\Tasks\TaskService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Description('Move a one-off (project) task to todo, doing or done. Moving to done scores the task today; moving out of done removes that score. Repeating habit actions have no status: use complete-task for them.')]
#[IsIdempotent]
final class MoveTaskTool extends TrakkTool
{
    protected function run(Request $request, User $user, CarbonImmutable $today): Response
    {
        $status = TaskStatus::from($request->validate(['status' => ['required', Rule::enum(TaskStatus::class)]])['status']);
        $task = $this->task($request, $user, $today);

        app(TaskService::class)->moveTo($task, $status, $today, ActivitySource::Mcp);
        $score = app(DayPointsService::class)->for($user, $today);

        return Response::text(sprintf('Moved "%s" to %s. Today %d/%d.', $task->title, $status->value, $score->points, $score->target));
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()->enum(array_map(fn (TaskStatus $status): string => $status->value, TaskStatus::cases()))->description('Where the task goes.')->required(),
            'title' => $schema->string()->description('Name of the task. Loose matching is fine.'),
            'task_id' => $schema->integer()->description('Exact task id from get-today or the goal board.'),
        ];
    }
}
