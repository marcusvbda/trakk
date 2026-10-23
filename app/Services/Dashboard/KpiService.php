<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Data\Dashboard\KpiData;
use App\Data\DayScore;
use App\Data\OnTimeRate;
use App\Data\Streak;
use App\Data\WeekScore;
use App\Enums\Tone;
use App\Models\Goal;
use App\Models\Task;
use App\Services\Goals\GoalProgressService;
use App\Support\Labels;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class KpiService
{
    public function __construct(
        private readonly GoalProgressService $progress,
        private readonly Labels $labels,
    ) {}

    /**
     * @param  Collection<int, Goal>  $goals  active, with `tasks` loaded
     * @return list<KpiData>
     */
    public function build(DayScore $day, Streak $streak, WeekScore $week, Collection $goals, OnTimeRate $onTime, CarbonImmutable $today): array
    {
        return [
            $this->pointsToday($day),
            $this->streak($streak),
            $this->thisWeek($week),
            $this->goalsOnPace($goals, $today),
            $this->overdue($goals, $today),
            $this->onTime($onTime),
        ];
    }

    private function pointsToday(DayScore $day): KpiData
    {
        $percent = min(100, $day->percentOfTarget());

        return new KpiData(
            'points-today',
            'points today',
            (string) $day->points,
            '/'.$day->target,
            $day->isTargetHit() ? Tone::Accent : Tone::Default,
            $percent,
            Tone::Accent,
            match (true) {
                $day->isTargetHit() => 'daily target hit',
                $day->points === 0 => 'first check of the day',
                default => $percent.'% of daily target',
            },
            $day->isTargetHit() ? Tone::Accent : Tone::Muted,
        );
    }

    private function streak(Streak $streak): KpiData
    {
        return new KpiData(
            'streak',
            'streak',
            (string) $streak->current,
            $streak->current === 1 ? ' day' : ' days',
            Tone::Default,
            $streak->record === 0 ? null : min(100, (int) round($streak->current / $streak->record * 100)),
            Tone::Dim,
            $streak->record > 0 ? 'record · '.$streak->record.' days' : 'start one today',
            Tone::Muted,
        );
    }

    private function thisWeek(WeekScore $week): KpiData
    {
        $change = $week->changePercent;

        return new KpiData(
            'this-week',
            'this week',
            (string) $week->points,
            ' pts',
            Tone::Default,
            null,
            Tone::Accent,
            $change === null ? 'no data last week' : ($change >= 0 ? '+' : '−').abs($change).'% vs last week',
            $change === null ? Tone::Muted : ($change >= 0 ? Tone::Accent : Tone::Danger),
        );
    }

    /**
     * @param  Collection<int, Goal>  $goals
     */
    private function goalsOnPace(Collection $goals, CarbonImmutable $today): KpiData
    {
        $active = $goals->count();

        if ($active === 0) {
            return new KpiData('goals-on-pace', 'goals on pace', '—', '', Tone::Default, null, Tone::Info, 'no active goals', Tone::Muted);
        }

        $onPace = $goals->filter(fn (Goal $goal): bool => $this->progress->pace($goal, $today)->delta >= 0)->count();
        $offPace = $active - $onPace;

        return new KpiData(
            'goals-on-pace',
            'goals on pace',
            (string) $onPace,
            '/'.$active,
            Tone::Default,
            (int) round($onPace / $active * 100),
            Tone::Info,
            $offPace > 0 ? $offPace.' off pace' : 'all on pace',
            $offPace > 0 ? Tone::Warning : Tone::Accent,
        );
    }

    /**
     * @param  Collection<int, Goal>  $goals
     */
    private function overdue(Collection $goals, CarbonImmutable $today): KpiData
    {
        $withOverdue = $goals
            ->map(fn (Goal $goal): array => [
                'goal' => $goal,
                'count' => $goal->tasks->filter(fn (Task $task): bool => $task->isOverdueOn($today))->count(),
            ])
            ->filter(fn (array $entry): bool => $entry['count'] > 0);
        $total = $withOverdue->sum('count');

        if ($total === 0) {
            return new KpiData('overdue', 'overdue', '0', '', Tone::Accent, null, Tone::Danger, 'all clear', Tone::Accent);
        }

        $mostBehind = $withOverdue->sortBy(fn (array $entry): int => $this->progress->pace($entry['goal'], $today)->delta)->first();

        return new KpiData(
            'overdue',
            'overdue',
            (string) $total,
            '',
            Tone::Danger,
            null,
            Tone::Danger,
            $mostBehind['count'].' from '.$this->labels->goalName($mostBehind['goal']),
            Tone::Muted,
        );
    }

    private function onTime(OnTimeRate $rate): KpiData
    {
        if ($rate->percent === null) {
            return new KpiData('on-time', 'on time · 30d', '—', '', Tone::Default, null, Tone::Muted, 'no deadlines in 30d', Tone::Muted);
        }

        return new KpiData(
            'on-time',
            'on time · 30d',
            (string) $rate->percent,
            '%',
            Tone::Default,
            $rate->percent,
            Tone::Muted,
            $rate->delivered.' of '.$rate->total.' delivered',
            Tone::Muted,
        );
    }
}
