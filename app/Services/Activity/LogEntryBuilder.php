<?php

declare(strict_types=1);

namespace App\Services\Activity;

use App\Data\Dashboard\LogEntryData;
use App\Enums\ActivityType;
use App\Models\Activity;
use App\Support\Labels;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class LogEntryBuilder
{
    public function __construct(private readonly Labels $labels) {}

    /**
     * @param  Collection<int, Activity>  $activities
     * @return list<LogEntryData>
     */
    public function entries(Collection $activities, string $timezone, CarbonImmutable $today): array
    {
        return $activities
            ->map(fn (Activity $activity): LogEntryData => new LogEntryData(
                $activity->id,
                $this->labels->logTime($activity->occurred_at, $timezone, $today),
                $activity->type->logKind(),
                $activity->message,
                $this->trailing($activity),
            ))
            ->values()
            ->all();
    }

    private function trailing(Activity $activity): ?string
    {
        return match ($activity->type) {
            ActivityType::TaskCompleted => $activity->points === null ? null : '+'.$activity->points,
            ActivityType::CheckIn => ($activity->meta['score'] ?? '?').'/'.($activity->meta['target'] ?? '?'),
            default => null,
        };
    }
}
