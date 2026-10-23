<?php

namespace App\Http\Requests\Settings;

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    use ProfileValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->profileRules($this->user()->id),
            'timezone' => ['required', 'timezone:all'],
            'daily_points_target' => ['required', 'integer', 'between:1,50'],
            'telegram_chat_id' => ['nullable', 'string', 'max:32', 'regex:/^-?\d+$/'],
        ];
    }
}
