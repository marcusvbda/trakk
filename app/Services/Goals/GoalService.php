<?php

declare(strict_types=1);

namespace App\Services\Goals;

use App\Data\GoalData;
use App\Enums\ActivitySource;
use App\Enums\GoalStatus;
use App\Enums\GoalType;
use App\Models\Goal;
use App\Models\User;
use App\Services\Activity\ActivityLogger;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class GoalService
{
    private const int MAX_TARGET_PER_WEEK = 7;

    public function __construct(private readonly ActivityLogger $activity) {}

    public function create(User $user, GoalData $data, ActivitySource $source = ActivitySource::Web): Goal
    {
        $this->assertValid($user, $data);

        return DB::transaction(function () use ($user, $data, $source): Goal {
            $goal = $user->goals()->create([
                ...$this->attributes($data),
                'slug' => $this->uniqueSlug($user, $data->slug ?? $data->title),
                'status' => GoalStatus::Active,
                'position' => (int) $user->goals()->max('position') + 1,
            ]);

            $this->activity->goalCreated($goal, $source);

            return $goal;
        });
    }

    /** The slug stays as it is unless `$data` carries a new one. */
    public function update(Goal $goal, GoalData $data): Goal
    {
        $user = $goal->loadMissing('user')->user;
        $this->assertValid($user, $data, $goal);

        if ($data->type !== $goal->type && $goal->tasks()->exists()) {
            throw new DomainException('The type of a goal with tasks cannot change.');
        }

        $goal->update([
            ...$this->attributes($data),
            'slug' => $data->slug === null ? $goal->slug : $this->uniqueSlug($user, $data->slug, $goal),
        ]);

        return $goal;
    }

    public function changeStatus(Goal $goal, GoalStatus $status): Goal
    {
        $goal->update(['status' => $status]);

        return $goal;
    }

    public function delete(Goal $goal): void
    {
        $goal->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(GoalData $data): array
    {
        return [
            'related_goal_id' => $data->relatedGoalId,
            'title' => $data->title,
            'icon' => $data->icon,
            'description' => $data->description,
            'done_criteria' => $data->doneCriteria,
            'type' => $data->type,
            'color' => $data->color,
            'priority' => $data->priority,
            'start_date' => $data->startDate,
            'due_date' => $data->dueDate,
            'target_per_week' => $data->targetPerWeek,
            'area' => $data->area,
        ];
    }

    private function assertValid(User $user, GoalData $data, ?Goal $goal = null): void
    {
        if ($data->dueDate->lessThanOrEqualTo($data->startDate)) {
            throw new InvalidArgumentException('The due date must be after the start date.');
        }

        if ($data->type === GoalType::Habit && ($data->targetPerWeek === null || $data->targetPerWeek < 1 || $data->targetPerWeek > self::MAX_TARGET_PER_WEEK)) {
            throw new InvalidArgumentException('A habit needs a target of 1 to 7 per week.');
        }

        if ($data->type === GoalType::Project && $data->targetPerWeek !== null) {
            throw new InvalidArgumentException('A project has no weekly target.');
        }

        if ($data->relatedGoalId !== null && ! $this->isOtherGoalOf($user, $data->relatedGoalId, $goal)) {
            throw new InvalidArgumentException('The related goal must be another goal of the same user.');
        }
    }

    private function isOtherGoalOf(User $user, int $goalId, ?Goal $goal): bool
    {
        return $goalId !== $goal?->id && $user->goals()->whereKey($goalId)->exists();
    }

    private function uniqueSlug(User $user, string $text, ?Goal $ignore = null): string
    {
        $root = Str::slug($text) ?: 'goal';
        $slug = $root;

        for ($suffix = 2; $this->slugIsTaken($user, $slug, $ignore); $suffix++) {
            $slug = $root.'-'.$suffix;
        }

        return $slug;
    }

    private function slugIsTaken(User $user, string $slug, ?Goal $ignore): bool
    {
        return $user->goals()
            ->where('slug', $slug)
            ->when($ignore !== null, fn ($query) => $query->whereKeyNot($ignore?->id))
            ->exists();
    }
}
