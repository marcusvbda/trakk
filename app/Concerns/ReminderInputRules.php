<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Data\ReminderData;
use App\Enums\ReminderChannel;
use App\Enums\ReminderKind;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Stringable;

/**
 * For the requests that create or edit a reminder.
 */
trait ReminderInputRules
{
    /**
     * @return array<string, list<ValidationRule|Stringable|string>>
     */
    protected function reminderRules(): array
    {
        return [
            'kind' => ['required', Rule::enum(ReminderKind::class)],
            'label' => ['required', 'string', 'max:80'],
            'time' => ['required', 'date_format:H:i'],
            'goal_id' => ['nullable', 'integer', Rule::exists('goals', 'id')->where('user_id', $this->user()->id)],
            'days_of_week' => ['nullable', 'array'],
            'days_of_week.*' => ['integer', 'between:1,7', 'distinct'],
            'fire_on' => ['nullable', 'date_format:Y-m-d'],
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => [Rule::enum(ReminderChannel::class), 'distinct'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function validateSchedule(Validator $validator): void
    {
        $isRecurring = ! empty($this->input('days_of_week'));
        $isOnce = $this->filled('fire_on');

        if ($isRecurring === $isOnce) {
            $validator->errors()->add('days_of_week', 'Pick the weekdays it repeats on, or one date.');
        }

        if (ReminderKind::tryFrom((string) $this->input('kind'))?->isGoalScoped() && ! $this->filled('goal_id')) {
            $validator->errors()->add('goal_id', 'This kind of reminder belongs to a goal.');
        }
    }

    public function toData(): ReminderData
    {
        $input = $this->validated();
        $days = $input['days_of_week'] ?? null;
        $fireOn = $input['fire_on'] ?? null;

        return new ReminderData(
            kind: ReminderKind::from($input['kind']),
            label: $input['label'],
            time: $input['time'],
            channels: array_map(ReminderChannel::from(...), $input['channels']),
            daysOfWeek: $days === null || $days === [] ? null : array_map('intval', $days),
            fireOn: $fireOn === null ? null : CarbonImmutable::parse($fireOn),
            goalId: isset($input['goal_id']) ? (int) $input['goal_id'] : null,
            isActive: (bool) $input['is_active'],
        );
    }
}
