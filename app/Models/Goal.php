<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GoalColor;
use App\Enums\GoalStatus;
use App\Enums\GoalType;
use App\Enums\Priority;
use Carbon\CarbonImmutable;
use Database\Factories\GoalFactory;
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
 * @property int $user_id
 * @property int|null $related_goal_id
 * @property string $title
 * @property string $slug
 * @property string|null $icon
 * @property string|null $description
 * @property string|null $done_criteria
 * @property GoalType $type
 * @property GoalColor $color
 * @property Priority $priority
 * @property CarbonImmutable $start_date
 * @property CarbonImmutable $due_date
 * @property int|null $target_per_week
 * @property string|null $area
 * @property GoalStatus $status
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id', 'related_goal_id', 'title', 'slug', 'icon', 'description', 'done_criteria', 'type', 'color',
    'priority', 'start_date', 'due_date', 'target_per_week', 'area', 'status', 'position',
])]
final class Goal extends Model
{
    /** @use HasFactory<GoalFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => GoalType::class,
            'color' => GoalColor::class,
            'priority' => Priority::class,
            'status' => GoalStatus::class,
            'start_date' => 'immutable_date',
            'due_date' => 'immutable_date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Goal, $this>
     */
    public function relatedGoal(): BelongsTo
    {
        return $this->belongsTo(self::class, 'related_goal_id');
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * @return HasMany<Reminder, $this>
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    /**
     * @return HasMany<Activity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * @param  Builder<Goal>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', GoalStatus::Active);
    }

    public function isHabit(): bool
    {
        return $this->type === GoalType::Habit;
    }

    public function isProject(): bool
    {
        return $this->type === GoalType::Project;
    }
}
