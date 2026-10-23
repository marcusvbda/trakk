<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\ActivitySource;
use App\Models\User;
use App\Services\Scoring\DayPointsService;
use App\Services\Tasks\CompletionService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Description("Undo today's completion of a task (a mistake). A one-off task goes back to todo. Pass `title` or `task_id`, like complete-task.")]
#[IsIdempotent]
final class UncompleteTaskTool extends TrakkTool
{
    protected function run(Request $request, User $user, CarbonImmutable $today): Response
    {
        $task = $this->task($request, $user, $today);
        app(CompletionService::class)->undo($task, $today, ActivitySource::Mcp);
        $score = app(DayPointsService::class)->for($user, $today);

        return Response::text(sprintf('Unchecked: "%s". Today %d/%d.', $task->title, $score->points, $score->target));
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->description('Name of the task, as the owner said it. Loose matching is fine.'),
            'task_id' => $schema->integer()->description('Exact task id from get-today. Use it when a title was ambiguous.'),
        ];
    }
}
