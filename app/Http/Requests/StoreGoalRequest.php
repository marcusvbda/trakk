<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Concerns\GoalInputRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StoreGoalRequest extends FormRequest
{
    use GoalInputRules;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->goalRules();
    }
}
