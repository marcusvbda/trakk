<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Task;
use Illuminate\Support\Collection;
use RuntimeException;

final class AmbiguousTaskException extends RuntimeException
{
    /**
     * @param  Collection<int, Task>  $candidates  with `goal` loaded
     */
    public function __construct(public readonly Collection $candidates)
    {
        parent::__construct(
            'Several tasks match; pick one by id: '.$candidates
                ->map(fn (Task $task): string => "[{$task->id}] {$task->title} ({$task->goal->title})")
                ->implode('; ').'.',
        );
    }
}
