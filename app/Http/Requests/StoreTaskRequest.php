<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Concerns\TaskInputRules;
use App\Enums\TaskStatus;
use App\Models\Goal;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Stringable;

final class StoreTaskRequest extends FormRequest
{
    use TaskInputRules;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('goal'));
    }

    /**
     * @return array<string, list<ValidationRule|Stringable|string>>
     */
    public function rules(): array
    {
        return [
            ...$this->taskRules(),
            'status' => ['nullable', Rule::in([TaskStatus::Todo->value, TaskStatus::Doing->value])],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var Goal $goal */
                $goal = $this->route('goal');

                $this->validateAgainstGoal($validator, $goal);
            },
        ];
    }
}
