<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Data\TaskData;
use App\Enums\TaskStatus;
use App\Models\Goal;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Validator;
use Stringable;

/**
 * For the requests that create or edit a task; they share every field and the checks against the goal's type.
 */
trait TaskInputRules
{
    /**
     * @return array<string, list<ValidationRule|Stringable|string>>
     */
    protected function taskRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'points' => ['required', 'integer', 'between:1,5'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'tag' => ['nullable', 'string', 'max:30'],
            'due_date' => ['nullable', 'date_format:Y-m-d'],
            'scheduled_time' => ['nullable', 'date_format:H:i'],
            'days_of_week' => ['nullable', 'array', 'min:1'],
            'days_of_week.*' => ['integer', 'between:1,7', 'distinct'],
        ];
    }

    protected function validateAgainstGoal(Validator $validator, Goal $goal): void
    {
        $hasWeekdays = ! empty($this->input('days_of_week'));

        if ($goal->isProject() && ($hasWeekdays || $this->filled('scheduled_time'))) {
            $validator->errors()->add('days_of_week', 'Project tasks are one-off: no weekdays or time.');
        }

        if ($goal->isHabit() && ! $hasWeekdays && ! $this->filled('due_date')) {
            $validator->errors()->add('days_of_week', 'Pick the weekdays this repeats on, or a due date for a one-off.');
        }
    }

    public function toData(): TaskData
    {
        $input = $this->validated();
        $dueDate = $input['due_date'] ?? null;
        $days = $input['days_of_week'] ?? null;

        return new TaskData(
            title: $input['title'],
            points: (int) $input['points'],
            status: isset($input['status']) ? TaskStatus::from($input['status']) : null,
            dueDate: $dueDate === null ? null : CarbonImmutable::parse($dueDate),
            scheduledTime: $input['scheduled_time'] ?? null,
            daysOfWeek: $days === null ? null : array_map('intval', $days),
            tag: $input['tag'] ?? null,
            notes: $input['notes'] ?? null,
        );
    }
}
