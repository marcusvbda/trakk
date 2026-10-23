<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Concerns\TaskInputRules;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Stringable;

final class UpdateTaskRequest extends FormRequest
{
    use TaskInputRules;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->task());
    }

    /**
     * @return array<string, list<ValidationRule|Stringable|string>>
     */
    public function rules(): array
    {
        return [
            ...$this->taskRules(),
            'status' => ['nullable', Rule::enum(TaskStatus::class)],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $task = $this->task();

                $this->validateAgainstGoal($validator, $task->goal);

                if ($task->isRecurring() !== ! empty($this->input('days_of_week'))) {
                    $validator->errors()->add('days_of_week', 'A task cannot change between repeating and one-off.');
                }
            },
        ];
    }

    private function task(): Task
    {
        return $this->route('task')->loadMissing('goal');
    }
}
