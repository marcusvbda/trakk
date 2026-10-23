<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Concerns\ReminderInputRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Stringable;

final class StoreReminderRequest extends FormRequest
{
    use ReminderInputRules;

    /**
     * @return array<string, list<ValidationRule|Stringable|string>>
     */
    public function rules(): array
    {
        return $this->reminderRules();
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [$this->validateSchedule(...)];
    }
}
