<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Completion;
use App\Models\Goal;
use App\Models\Task;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * The owner as of `$today`: active goals with their tasks and completions, and all completions. What reminder
 * messages and task lookups are written from.
 */
final readonly class UserSnapshot
{
    /**
     * @param  Collection<int, Goal>  $goals  active, with `tasks.completions` loaded and each task's `goal` set
     * @param  Collection<int, Completion>  $completions
     */
    public function __construct(
        public User $user,
        public CarbonImmutable $today,
        public Collection $goals,
        public Collection $completions,
    ) {}

    /**
     * @return Collection<int, Task>
     */
    public function tasks(): Collection
    {
        return $this->goals->flatMap(fn (Goal $goal): Collection => $goal->tasks)->values();
    }
}
