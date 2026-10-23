<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\ReplanData;
use App\Enums\ReplanTarget;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Support\Clock;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class ReplanRequest extends FormRequest
{
    /** @var Collection<int, Task>|null */
    private ?Collection $tasks = null;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'task_ids' => ['required', 'array', 'min:1'],
            'task_ids.*' => ['integer', 'distinct'],
            'target' => ['required', Rule::enum(ReplanTarget::class)],
            'date' => ['required_if:target,'.ReplanTarget::Date->value, 'nullable', 'date_format:Y-m-d'],
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

                $this->validateTasks($validator);
                $this->validateDate($validator);
            },
        ];
    }

    public function toData(): ReplanData
    {
        $date = $this->validated('date');

        return new ReplanData(
            ReplanTarget::from($this->validated('target')),
            $date === null ? null : CarbonImmutable::parse($date),
        );
    }

    /**
     * The owner's tasks among the requested ids.
     *
     * @return Collection<int, Task>
     */
    public function tasks(): Collection
    {
        return $this->tasks ??= Task::query()
            ->with('goal')
            ->ownedBy($this->user())
            ->whereIn('id', $this->validated('task_ids'))
            ->get();
    }

    private function validateTasks(Validator $validator): void
    {
        $tasks = $this->tasks();

        if ($tasks->count() !== count($this->validated('task_ids'))) {
            $validator->errors()->add('task_ids', 'Some of these tasks do not exist.');

            return;
        }

        if ($tasks->contains(fn (Task $task): bool => $task->isRecurring() || $task->status === TaskStatus::Done)) {
            $validator->errors()->add('task_ids', 'Only open one-off tasks can be replanned.');
        }
    }

    private function validateDate(Validator $validator): void
    {
        if ($this->validated('target') !== ReplanTarget::Date->value) {
            return;
        }

        if (CarbonImmutable::parse($this->validated('date'))->lessThan(app(Clock::class)->today($this->user()))) {
            $validator->errors()->add('date', 'Pick a date that is today or later.');
        }
    }
}
