<?php

declare(strict_types=1);

namespace App\Services\Tasks;

use App\Exceptions\AmbiguousTaskException;
use App\Exceptions\TaskNotFoundException;
use App\Models\Task;
use App\Models\User;
use App\Services\Dashboard\TodayListBuilder;
use App\Services\Dashboard\UserSnapshotBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Finds the task Claude means: by id, or by a title typed loosely ("speaking" for "Speaking · 30 min").
 */
final class TaskLookupService
{
    private const float MIN_SIMILARITY = 0.6;

    /** Two guesses closer than this are not told apart. */
    private const float TIE_MARGIN = 0.05;

    public function __construct(
        private readonly UserSnapshotBuilder $snapshots,
        private readonly TodayListBuilder $todayList,
    ) {}

    /**
     * @throws TaskNotFoundException
     * @throws AmbiguousTaskException
     */
    public function find(User $user, CarbonImmutable $today, ?int $id, ?string $title): Task
    {
        if ($id !== null) {
            return Task::query()->with(['goal', 'completions'])->ownedBy($user)->find($id)
                ?? throw new TaskNotFoundException("No task with id {$id}.");
        }

        $needle = $this->normalise((string) $title);

        if ($needle === '') {
            throw new TaskNotFoundException('Give a task_id or a title.');
        }

        $tasks = $this->snapshots->for($user, $today)->tasks();
        $todayIds = collect($this->todayList->build($tasks, $today))->pluck('id');

        foreach ([$this->exactMatches($tasks, $needle), $this->containedMatches($tasks, $needle)] as $matches) {
            if ($matches->isNotEmpty()) {
                return $this->pick($matches, $todayIds);
            }
        }

        return $this->closest($tasks->whereIn('id', $todayIds), $needle, $title);
    }

    /**
     * @param  Collection<int, Task>  $tasks
     * @return Collection<int, Task>
     */
    private function exactMatches(Collection $tasks, string $needle): Collection
    {
        return $tasks->filter(fn (Task $task): bool => $this->normalise($task->title) === $needle)->values();
    }

    /**
     * @param  Collection<int, Task>  $tasks
     * @return Collection<int, Task>
     */
    private function containedMatches(Collection $tasks, string $needle): Collection
    {
        return $tasks->filter(function (Task $task) use ($needle): bool {
            $title = $this->normalise($task->title);

            // A title with no letters or digits normalises to '' and would be "contained" in every query.
            return $title !== '' && (str_contains($title, $needle) || str_contains($needle, $title));
        })->values();
    }

    /**
     * One match wins; among several, the only one on today's list does; otherwise the caller must choose.
     *
     * @param  Collection<int, Task>  $matches
     * @param  Collection<int, int>  $todayIds
     */
    private function pick(Collection $matches, Collection $todayIds): Task
    {
        if ($matches->count() === 1) {
            return $matches->first();
        }

        $onToday = $matches->filter(fn (Task $task): bool => $todayIds->contains($task->id));

        if ($onToday->count() === 1) {
            return $onToday->first();
        }

        throw new AmbiguousTaskException($matches);
    }

    /**
     * @param  Collection<int, Task>  $todayTasks
     */
    private function closest(Collection $todayTasks, string $needle, ?string $title): Task
    {
        $ranked = $todayTasks
            ->map(fn (Task $task): array => ['task' => $task, 'score' => $this->similarity($this->normalise($task->title), $needle)])
            ->filter(fn (array $guess): bool => $guess['score'] >= self::MIN_SIMILARITY)
            ->sortByDesc('score')
            ->values();

        if ($ranked->isEmpty()) {
            throw new TaskNotFoundException("No task matches \"{$title}\".");
        }

        $second = $ranked->get(1);

        if ($second !== null && $ranked[0]['score'] - $second['score'] < self::TIE_MARGIN) {
            throw new AmbiguousTaskException($ranked->take(3)->pluck('task'));
        }

        return $ranked[0]['task'];
    }

    private function similarity(string $a, string $b): float
    {
        $longest = max(strlen($a), strlen($b));

        return $longest === 0 ? 1.0 : 1 - levenshtein(substr($a, 0, 255), substr($b, 0, 255)) / $longest;
    }

    private function normalise(string $text): string
    {
        return trim((string) preg_replace('/[^a-z0-9]+/', ' ', Str::lower(Str::ascii($text))));
    }
}
