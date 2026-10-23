<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TaskStatus;
use Carbon\CarbonImmutable;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $goal_id
 * @property string $title
 * @property string|null $notes
 * @property TaskStatus $status
 * @property int $points
 * @property CarbonImmutable|null $due_date
 * @property string|null $scheduled_time
 * @property list<int>|null $days_of_week
 * @property string|null $tag
 * @property int $position
 * @property CarbonImmutable|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'goal_id', 'title', 'notes', 'status', 'points', 'due_date', 'scheduled_time', 'days_of_week', 'tag',
    'position', 'completed_at',
])]
final class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'due_date' => 'immutable_date',
            'days_of_week' => 'array',
            'completed_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Goal, $this>
     */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    /**
     * @return HasMany<ChecklistItem, $this>
     */
    public function checklistItems(): HasMany
    {
        return $this->hasMany(ChecklistItem::class);
    }

    /**
     * @return HasMany<Completion, $this>
     */
    public function completions(): HasMany
    {
        return $this->hasMany(Completion::class);
    }

    /**
     * @param  Builder<Task>  $query
     */
    #[Scope]
    protected function oneOff(Builder $query): void
    {
        $query->whereNull('days_of_week');
    }

    /**
     * @param  Builder<Task>  $query
     */
    #[Scope]
    protected function recurring(Builder $query): void
    {
        $query->whereNotNull('days_of_week');
    }

    /**
     * @param  Builder<Task>  $query
     */
    #[Scope]
    protected function notDone(Builder $query): void
    {
        $query->where('status', '!=', TaskStatus::Done);
    }

    /**
     * @param  Builder<Task>  $query
     */
    #[Scope]
    protected function overdue(Builder $query, CarbonImmutable $today): void
    {
        $query->oneOff()->notDone()->whereDate('due_date', '<', $today);
    }

    /**
     * @param  Builder<Task>  $query
     */
    #[Scope]
    protected function ownedBy(Builder $query, User $user): void
    {
        $query->whereHas('goal', fn (Builder $goals) => $goals->where('user_id', $user->id));
    }

    public function isRecurring(): bool
    {
        return $this->days_of_week !== null;
    }

    public function isOverdueOn(CarbonImmutable $day): bool
    {
        return ! $this->isRecurring()
            && $this->status !== TaskStatus::Done
            && $this->due_date !== null
            && $this->due_date->lessThan($day);
    }

    public function isScheduledOn(CarbonImmutable $day): bool
    {
        return $this->days_of_week !== null && in_array($day->dayOfWeekIso, $this->days_of_week, true);
    }
}
