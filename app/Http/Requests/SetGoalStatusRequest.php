<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\GoalStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SetGoalStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('goal'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(GoalStatus::class)],
        ];
    }

    public function toStatus(): GoalStatus
    {
        return GoalStatus::from($this->validated('status'));
    }
}
