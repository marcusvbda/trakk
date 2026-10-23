<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Data\TaskData;
use App\Enums\ActivitySource;
use App\Models\Goal;
use App\Models\User;
use App\Services\Tasks\TaskService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Add a task to a goal. Call list-goals first to get the goal id. Project goals take one-off tasks (optional due_date). Habit goals take either days_of_week (ISO 1 = Monday to 7 = Sunday) for an action that repeats, or a due_date for a one-off. Points are 1 to 5.')]
final class CreateTaskTool extends TrakkTool
{
    protected function run(Request $request, User $user, CarbonImmutable $today): Response
    {
        $input = $request->validate([
            'goal_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:160'],
            'points' => ['required', 'integer', 'between:1,5'],
            'due_date' => ['nullable', 'date_format:Y-m-d'],
            'scheduled_time' => ['nullable', 'date_format:H:i'],
            'days_of_week' => ['nullable', 'array', 'min:1'],
            'days_of_week.*' => ['integer', 'between:1,7', 'distinct'],
            'tag' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $goal = Goal::query()->whereBelongsTo($user)->findOrFail($input['goal_id']);
        $task = app(TaskService::class)->create($goal, new TaskData(
            title: $input['title'],
            points: (int) $input['points'],
            dueDate: isset($input['due_date']) ? CarbonImmutable::parse($input['due_date']) : null,
            scheduledTime: $input['scheduled_time'] ?? null,
            daysOfWeek: isset($input['days_of_week']) ? array_map('intval', $input['days_of_week']) : null,
            tag: $input['tag'] ?? null,
            notes: $input['notes'] ?? null,
        ), ActivitySource::Mcp);

        return Response::text(sprintf('Created [%d] "%s" +%d pts in "%s".', $task->id, $task->title, $task->points, $goal->title));
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'goal_id' => $schema->integer()->description('Goal id from list-goals.')->required(),
            'title' => $schema->string()->description('What to do, short.')->required(),
            'points' => $schema->integer()->description('Worth of the task, 1 (small) to 5 (big).')->required(),
            'due_date' => $schema->string()->description('Due date, YYYY-MM-DD. For a one-off task.'),
            'days_of_week' => $schema->array()->items($schema->integer())->description('Habit goals only: weekdays it repeats on, ISO numbers 1 (Monday) to 7 (Sunday).'),
            'scheduled_time' => $schema->string()->description('Habit goals only: time of day, HH:MM.'),
            'tag' => $schema->string()->description('Optional short label, e.g. "critical".'),
            'notes' => $schema->string()->description('Optional longer note.'),
        ];
    }
}
