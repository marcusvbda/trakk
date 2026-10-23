<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Concerns\GoalInputRules;
use App\Enums\GoalType;
use App\Models\Goal;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class UpdateGoalRequest extends FormRequest
{
    use GoalInputRules;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->goal());
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->goalRules($this->goal()),
            'slug' => [
                'nullable',
                'string',
                'max:80',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('goals', 'slug')->where('user_id', $this->user()->id)->ignore($this->goal()->id),
            ],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if (GoalType::from($this->validated('type')) !== $this->goal()->type && $this->goal()->tasks()->exists()) {
                    $validator->errors()->add('type', 'The type cannot change once the goal has tasks.');
                }
            },
        ];
    }

    private function goal(): Goal
    {
        return $this->route('goal');
    }
}
