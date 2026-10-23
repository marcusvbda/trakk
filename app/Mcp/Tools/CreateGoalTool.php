<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Data\GoalData;
use App\Enums\ActivitySource;
use App\Enums\GoalColor;
use App\Enums\GoalType;
use App\Enums\Priority;
use App\Models\User;
use App\Services\Goals\GoalService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Create a goal. A habit repeats and needs target_per_week (1 to 7); a project has no weekly target and is finished through its tasks. Both need a start and a due date (YYYY-MM-DD, due after start). Returns the goal id: use it with create-task.')]
final class CreateGoalTool extends TrakkTool
{
    protected function run(Request $request, User $user, CarbonImmutable $today): Response
    {
        $input = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::enum(GoalType::class)],
            'color' => ['required', Rule::enum(GoalColor::class)],
            'priority' => ['required', Rule::enum(Priority::class)],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'due_date' => ['required', 'date_format:Y-m-d'],
            'target_per_week' => ['nullable', 'integer', 'between:1,7'],
            'area' => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:2000'],
            'done_criteria' => ['nullable', 'string', 'max:500'],
        ]);

        $goal = app(GoalService::class)->create($user, new GoalData(
            title: $input['title'],
            type: GoalType::from($input['type']),
            color: GoalColor::from($input['color']),
            priority: Priority::from($input['priority']),
            startDate: CarbonImmutable::parse($input['start_date']),
            dueDate: CarbonImmutable::parse($input['due_date']),
            targetPerWeek: $input['target_per_week'] ?? null,
            area: $input['area'] ?? null,
            description: $input['description'] ?? null,
            doneCriteria: $input['done_criteria'] ?? null,
        ), ActivitySource::Mcp);

        return Response::text(sprintf('Created goal [%d] "%s" (%s), due %s.', $goal->id, $goal->title, $goal->type->value, $goal->due_date->toDateString()));
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->description('Name of the goal.')->required(),
            'type' => $schema->string()->enum(array_map(fn (GoalType $type): string => $type->value, GoalType::cases()))->description('habit repeats weekly; project ends at a deadline.')->required(),
            'color' => $schema->string()->enum(array_map(fn (GoalColor $color): string => $color->value, GoalColor::cases()))->description('Accent color of the goal.')->required(),
            'priority' => $schema->string()->enum(array_map(fn (Priority $priority): string => $priority->value, Priority::cases()))->description('How much it matters.')->required(),
            'start_date' => $schema->string()->description('YYYY-MM-DD.')->required(),
            'due_date' => $schema->string()->description('YYYY-MM-DD, after start_date.')->required(),
            'target_per_week' => $schema->integer()->description('Habits only: how many days a week, 1 to 7.'),
            'area' => $schema->string()->description('Optional life area, e.g. "career".'),
            'description' => $schema->string()->description('Optional: why it matters.'),
            'done_criteria' => $schema->string()->description('Optional: how to know it is finished.'),
        ];
    }
}
