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

#[Description('Mark a task as done for today and return the points earned and the day total. Pass `title` (it may be loose: "speaking" finds "Speaking · 30 min") or `task_id` from get-today. If several tasks match, it lists their ids so you can ask which one.')]
#[IsIdempotent]
final class CompleteTaskTool extends TrakkTool
{
    protected function run(Request $request, User $user, CarbonImmutable $today): Response
    {
        $task = $this->task($request, $user, $today);
        $completion = app(CompletionService::class)->complete($task, $today, ActivitySource::Mcp);
        $score = app(DayPointsService::class)->for($user, $today);

        return Response::text(sprintf(
            '%s: "%s" +%d pts. Today %d/%d.',
            $completion->wasRecentlyCreated ? 'Done' : 'Already done today',
            $task->title, $completion->points, $score->points, $score->target,
        ));
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
