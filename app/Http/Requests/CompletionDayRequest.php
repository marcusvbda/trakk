<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Task;
use App\Services\Goals\HabitWeekService;
use App\Support\Clock;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class CompletionDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('task'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'on' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty() || ! $this->filled('on')) {
                    return;
                }

                /** @var Task $task */
                $task = $this->route('task');

                if (! app(HabitWeekService::class)->canCheck($task, $this->day(), app(Clock::class)->today($this->user()))) {
                    $validator->errors()->add('on', 'Only scheduled days of this week, up to today, can be checked.');
                }
            },
        ];
    }

    /** The day being checked: the requested one, or today when none is given. */
    public function day(?CarbonImmutable $today = null): CarbonImmutable
    {
        return $this->filled('on')
            ? CarbonImmutable::parse((string) $this->input('on'))
            : ($today ?? app(Clock::class)->today($this->user()));
    }
}
