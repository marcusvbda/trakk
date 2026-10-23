<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Data\ReplanData;
use App\Enums\ActivitySource;
use App\Enums\ReplanTarget;
use App\Models\Task;
use App\Models\User;
use App\Services\Tasks\TaskService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Description('Push open one-off tasks (usually the overdue ones from get-today) to a new due date: today, tomorrow, next_monday or a specific date. Recurring and done tasks cannot be replanned.')]
#[IsIdempotent]
final class ReplanTasksTool extends TrakkTool
{
    protected function run(Request $request, User $user, CarbonImmutable $today): Response
    {
        $input = $request->validate([
            'task_ids' => ['required', 'array', 'min:1'],
            'task_ids.*' => ['integer', 'distinct'],
            'target' => ['required', Rule::enum(ReplanTarget::class)],
            'date' => ['required_if:target,'.ReplanTarget::Date->value, 'nullable', 'date_format:Y-m-d'],
        ]);

        $tasks = Task::query()->with('goal')->ownedBy($user)->whereIn('id', $input['task_ids'])->get();

        if ($tasks->count() !== count($input['task_ids'])) {
            return Response::error('Some of these tasks do not exist. Use get-today to see valid ids.');
        }

        $target = ReplanTarget::from($input['target']);
        $date = isset($input['date']) ? CarbonImmutable::parse($input['date']) : null;

        app(TaskService::class)->replan($tasks, new ReplanData($target, $date), $today, ActivitySource::Mcp);

        return Response::text(sprintf('Replanned %d %s to %s.', $tasks->count(), $tasks->count() === 1 ? 'task' : 'tasks', $tasks->first()->refresh()->due_date->toDateString()));
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'task_ids' => $schema->array()->items($schema->integer())->description('Ids of the tasks to move, from get-today.')->required(),
            'target' => $schema->string()->enum(array_map(fn (ReplanTarget $target): string => $target->value, ReplanTarget::cases()))->description('When to move them. Use "date" together with `date`.')->required(),
            'date' => $schema->string()->description('YYYY-MM-DD, today or later. Only with target "date".'),
        ];
    }
}
