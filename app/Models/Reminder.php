<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReminderChannel;
use App\Enums\ReminderKind;
use Carbon\CarbonImmutable;
use Database\Factories\ReminderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $goal_id
 * @property ReminderKind $kind
 * @property string $label
 * @property string $time
 * @property list<int>|null $days_of_week
 * @property CarbonImmutable|null $fire_on
 * @property Collection<int, ReminderChannel> $channels
 * @property bool $is_active
 * @property CarbonImmutable|null $last_sent_at
 * @property string|null $last_error
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id', 'goal_id', 'kind', 'label', 'time', 'days_of_week', 'fire_on', 'channels', 'is_active',
    'last_sent_at', 'last_error',
])]
final class Reminder extends Model
{
    /** @use HasFactory<ReminderFactory> */
    use HasFactory;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'kind' => ReminderKind::class,
            'days_of_week' => 'array',
            'fire_on' => 'immutable_date',
            'channels' => AsEnumCollection::of(ReminderChannel::class),
            'is_active' => 'boolean',
            'last_sent_at' => 'immutable_datetime',
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
    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    public function isOnce(): bool
    {
        return $this->fire_on !== null;
    }
}
