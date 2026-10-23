<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Data\GoalData;
use App\Enums\GoalColor;
use App\Enums\GoalType;
use App\Enums\Priority;
use App\Models\Goal;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Stringable;

/**
 * For the requests that create or edit a goal; they share every field but the slug.
 */
trait GoalInputRules
{
    /**
     * @return array<string, list<ValidationRule|Stringable|string>>
     */
    protected function goalRules(?Goal $goal = null): array
    {
        $isHabit = $this->input('type') === GoalType::Habit->value;

        return [
            'title' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::enum(GoalType::class)],
            'color' => ['required', Rule::enum(GoalColor::class)],
            'priority' => ['required', Rule::enum(Priority::class)],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'due_date' => ['required', 'date_format:Y-m-d', 'after:start_date'],
            'target_per_week' => [Rule::requiredIf($isHabit), Rule::prohibitedIf(! $isHabit), 'nullable', 'integer', 'between:1,7'],
            'area' => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:2000'],
            'done_criteria' => ['nullable', 'string', 'max:500'],
            'related_goal_id' => [
                'nullable',
                'integer',
                Rule::exists('goals', 'id')->where('user_id', $this->user()->id),
                Rule::notIn($goal === null ? [] : [$goal->id]),
            ],
        ];
    }

    public function toData(): GoalData
    {
        $input = $this->validated();

        return new GoalData(
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
            relatedGoalId: $input['related_goal_id'] ?? null,
            slug: $input['slug'] ?? null,
        );
    }
}
