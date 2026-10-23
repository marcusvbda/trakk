<?php

declare(strict_types=1);

namespace App\Support\Mocks;

/**
 * Prototype data shaped like the future dashboard Resources. Replaced by the M3 read model.
 */
final class DashboardMock
{
    private const DAILY_TARGET = 14;

    /**
     * @return array<string, mixed>
     */
    public static function props(): array
    {
        return [
            'dailyTarget' => self::DAILY_TARGET,
            'kpis' => self::kpis(),
            'actions' => self::actions(),
            'overdue' => self::overdue(),
            'suggestion' => 'knock out the visa documents on Saturday morning. It unblocks your most behind goal and clears 2 items from red.',
            'upcoming' => self::upcoming(),
            'goalsSummary' => ['active' => 6, 'onPace' => 4],
            'goals' => self::goals(),
            'pointsChart' => self::pointsChart(),
            'consistency' => self::consistency(),
            'reminders' => self::reminders(),
            'focusProject' => self::focusProject(),
            'log' => self::log(),
            'status' => [
                'mcpTools' => 5,
                'nextReminderAt' => '21:30',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function kpis(): array
    {
        return [
            ['id' => 'points-today', 'label' => 'points today', 'value' => '9', 'suffix' => '/14', 'valueTone' => 'default', 'progress' => 64, 'progressTone' => 'accent', 'sub' => '64% of daily target', 'subTone' => 'muted'],
            ['id' => 'streak', 'label' => 'streak', 'value' => '12', 'suffix' => ' days', 'valueTone' => 'default', 'progress' => 57, 'progressTone' => 'dim', 'sub' => 'record · 21 days', 'subTone' => 'muted'],
            ['id' => 'this-week', 'label' => 'this week', 'value' => '56', 'suffix' => ' pts', 'valueTone' => 'default', 'progress' => null, 'progressTone' => 'accent', 'sub' => '+8% vs last week', 'subTone' => 'accent'],
            ['id' => 'goals-on-pace', 'label' => 'goals on pace', 'value' => '4', 'suffix' => '/6', 'valueTone' => 'default', 'progress' => 67, 'progressTone' => 'info', 'sub' => '2 off pace', 'subTone' => 'warning'],
            ['id' => 'overdue', 'label' => 'overdue', 'value' => '4', 'suffix' => '', 'valueTone' => 'danger', 'progress' => null, 'progressTone' => 'danger', 'sub' => '2 from nomad visa', 'subTone' => 'muted'],
            ['id' => 'on-time', 'label' => 'on time · 30d', 'value' => '86', 'suffix' => '%', 'valueTone' => 'default', 'progress' => 86, 'progressTone' => 'muted', 'sub' => '31 of 36 delivered', 'subTone' => 'muted'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function actions(): array
    {
        return [
            ['id' => 'a1', 'title' => 'Speaking · 30 min', 'goalName' => 'ielts 7.0', 'goalColor' => 'blue', 'points' => 3, 'timeLabel' => '07:00', 'isDueToday' => false, 'isDone' => true],
            ['id' => 'a2', 'title' => 'Vocabulary · 20 words', 'goalName' => 'ielts 7.0', 'goalColor' => 'blue', 'points' => 1, 'timeLabel' => '07:40', 'isDueToday' => false, 'isDone' => true],
            ['id' => 'a3', 'title' => 'Upper body workout', 'goalName' => 'training 4x', 'goalColor' => 'cyan', 'points' => 3, 'timeLabel' => '12:30', 'isDueToday' => false, 'isDone' => true],
            ['id' => 'a4', 'title' => 'Log meals', 'goalName' => 'diet', 'goalColor' => 'pink', 'points' => 2, 'timeLabel' => 'all day', 'isDueToday' => false, 'isDone' => true],
            ['id' => 'a5', 'title' => 'Book sworn translation', 'goalName' => 'nomad visa', 'goalColor' => 'amber', 'points' => 4, 'timeLabel' => 'today', 'isDueToday' => true, 'isDone' => false],
            ['id' => 'a6', 'title' => 'Request apostilled marriage cert', 'goalName' => 'nomad visa', 'goalColor' => 'amber', 'points' => 3, 'timeLabel' => 'today', 'isDueToday' => true, 'isDone' => false],
            ['id' => 'a7', 'title' => 'Reading · 20 pages', 'goalName' => '12 books', 'goalColor' => 'sand', 'points' => 1, 'timeLabel' => '22:00', 'isDueToday' => false, 'isDone' => false],
            ['id' => 'a8', 'title' => 'Review moving budget', 'goalName' => 'savings', 'goalColor' => 'violet', 'points' => 2, 'timeLabel' => 'anytime', 'isDueToday' => false, 'isDone' => false],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function overdue(): array
    {
        return [
            ['id' => 'o1', 'title' => 'Read chapters 8–10', 'goalName' => '12 books', 'goalColor' => 'sand', 'dueLabel' => '18 oct', 'daysLate' => 5],
            ['id' => 'o2', 'title' => 'Apostille diploma', 'goalName' => 'nomad visa', 'goalColor' => 'amber', 'dueLabel' => '20 oct', 'daysLate' => 3],
            ['id' => 'o3', 'title' => 'Writing mock test', 'goalName' => 'ielts 7.0', 'goalColor' => 'blue', 'dueLabel' => '21 oct', 'daysLate' => 2],
            ['id' => 'o4', 'title' => 'Bank statements · 3 months', 'goalName' => 'nomad visa', 'goalColor' => 'amber', 'dueLabel' => '22 oct', 'daysLate' => 1],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function upcoming(): array
    {
        return [
            ['id' => 'u1', 'dayLabel' => 'SAT 24', 'title' => 'Health insurance · quotes', 'goalName' => 'nomad visa', 'goalColor' => 'amber'],
            ['id' => 'u2', 'dayLabel' => 'SUN 25', 'title' => 'Weekly review', 'goalName' => 'system', 'goalColor' => null],
            ['id' => 'u3', 'dayLabel' => 'MON 26', 'title' => 'Full mock test', 'goalName' => 'ielts 7.0', 'goalColor' => 'blue'],
            ['id' => 'u4', 'dayLabel' => 'TUE 27', 'title' => 'Weekly weigh-in', 'goalName' => 'diet', 'goalColor' => 'pink'],
            ['id' => 'u5', 'dayLabel' => 'WED 28', 'title' => 'Sworn translation delivery', 'goalName' => 'nomad visa', 'goalColor' => 'amber'],
            ['id' => 'u6', 'dayLabel' => 'FRI 30', 'title' => 'Apostilled marriage certificate', 'goalName' => 'nomad visa', 'goalColor' => 'amber'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function goals(): array
    {
        return [
            ['id' => 'nomad-visa', 'title' => 'Nomad visa · Spain', 'type' => 'project', 'priority' => 'high', 'color' => 'amber', 'startLabel' => '01 jul', 'dueLabel' => '15 jan', 'daysLeft' => 84, 'frequencyLabel' => 'no frequency', 'percent' => 45, 'expectedPercent' => 58, 'pace' => ['status' => 'behind', 'delta' => -13], 'week' => null, 'progressLabel' => '9/20 tasks', 'hasBoard' => true],
            ['id' => 'ielts', 'title' => 'IELTS 7.0', 'type' => 'habit', 'priority' => 'high', 'color' => 'blue', 'startLabel' => '15 aug', 'dueLabel' => '20 mar', 'daysLeft' => 148, 'frequencyLabel' => '5x/wk', 'percent' => 34, 'expectedPercent' => 32, 'pace' => ['status' => 'on_pace', 'delta' => 2], 'week' => self::week(['done', 'done', 'missed', 'done', 'done', 'upcoming', 'upcoming'], 5), 'progressLabel' => null, 'hasBoard' => false],
            ['id' => 'training', 'title' => 'Train 4x a week', 'type' => 'habit', 'priority' => 'medium', 'color' => 'cyan', 'startLabel' => '01 sep', 'dueLabel' => '31 dec', 'daysLeft' => 69, 'frequencyLabel' => '4x/wk', 'percent' => 45, 'expectedPercent' => 43, 'pace' => ['status' => 'on_pace', 'delta' => 2], 'week' => self::week(['done', 'missed', 'done', 'missed', 'done', 'upcoming', 'upcoming'], 4), 'progressLabel' => null, 'hasBoard' => false],
            ['id' => 'diet', 'title' => 'Diet · calorie deficit', 'type' => 'habit', 'priority' => 'medium', 'color' => 'pink', 'startLabel' => '15 sep', 'dueLabel' => '31 dec', 'daysLeft' => 69, 'frequencyLabel' => 'daily', 'percent' => 42, 'expectedPercent' => 36, 'pace' => ['status' => 'ahead', 'delta' => 6], 'week' => self::week(['done', 'done', 'done', 'done', 'done', 'upcoming', 'upcoming'], 7), 'progressLabel' => null, 'hasBoard' => false],
            ['id' => 'moving-fund', 'title' => 'Moving fund', 'type' => 'project', 'priority' => 'medium', 'color' => 'violet', 'startLabel' => '01 aug', 'dueLabel' => '28 feb', 'daysLeft' => 128, 'frequencyLabel' => 'monthly deposit', 'percent' => 41, 'expectedPercent' => 39, 'pace' => ['status' => 'on_pace', 'delta' => 2], 'week' => null, 'progressLabel' => '3 of 7 deposits', 'hasBoard' => false],
            ['id' => 'books', 'title' => 'Read 12 books in 2026', 'type' => 'habit', 'priority' => 'low', 'color' => 'sand', 'startLabel' => '01 jan', 'dueLabel' => '31 dec', 'daysLeft' => 69, 'frequencyLabel' => '20 pages/day', 'percent' => 75, 'expectedPercent' => 81, 'pace' => ['status' => 'behind', 'delta' => -6], 'week' => self::week(['done', 'done', 'missed', 'missed', 'missed', 'upcoming', 'upcoming'], 7), 'progressLabel' => null, 'hasBoard' => false],
        ];
    }

    /**
     * @param  list<string>  $days
     * @return array{days: list<string>, doneCount: int, targetCount: int}
     */
    private static function week(array $days, int $targetCount): array
    {
        return [
            'days' => $days,
            'doneCount' => count(array_filter($days, fn (string $day): bool => $day === 'done')),
            'targetCount' => $targetCount,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function pointsChart(): array
    {
        $points = [12, 15, 9, 14, 16, 11, 4, 13, 15, 17, 14, 8, 12, 16, 18, 15, 13, 6, 14, 15, 17, 16, 12, 15, 14, 18, 16, 13, 15, 9];

        return [
            'target' => self::DAILY_TARGET,
            'scaleMax' => 20,
            'days' => array_map(fn (int $value, int $index): array => [
                'points' => $value,
                'tier' => match (true) {
                    $value >= self::DAILY_TARGET => 'target',
                    $value >= 10 => 'good',
                    default => 'low',
                },
                'label' => ($index === count($points) - 1 ? 'today · ' : '').$value.' pts',
            ], $points, array_keys($points)),
            'startLabel' => '24 sep',
            'middleLabel' => '08 oct',
            'average' => 13.4,
            'bestDay' => 18,
            'daysAtTarget' => 18,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function consistency(): array
    {
        $todayIndex = 4;
        $weeks = [];
        $goodDays = 0;
        $totalDays = 0;

        for ($week = 0; $week < 12; $week++) {
            $days = [];
            for ($day = 0; $day < 7; $day++) {
                if ($week === 11 && $day > $todayIndex) {
                    $days[] = null;

                    continue;
                }

                $level = match (true) {
                    $week === 11 && $day === $todayIndex => 2,
                    default => self::heatLevel($week, $day),
                };

                $totalDays++;
                $goodDays += $level >= 3 ? 1 : 0;
                $days[] = $level;
            }
            $weeks[] = $days;
        }

        return [
            'weeks' => $weeks,
            'goodDays' => $goodDays,
            'totalDays' => $totalDays,
            'goodPercent' => (int) round($goodDays / $totalDays * 100),
        ];
    }

    private static function heatLevel(int $week, int $day): int
    {
        $level = (($week * 7 + $day) * 37 + $week * 3) % 9;
        $level = $level > 4 ? $level - 4 : $level;

        return $day >= 5 ? max(0, $level - 1) : $level;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function reminders(): array
    {
        return [
            ['id' => 'r1', 'timeLabel' => '07:30', 'label' => 'Daily brief', 'frequencyLabel' => 'daily', 'channelsLabel' => 'telegram', 'isActive' => true],
            ['id' => 'r2', 'timeLabel' => '21:30', 'label' => 'Evening check-in', 'frequencyLabel' => 'daily', 'channelsLabel' => 'telegram', 'isActive' => true],
            ['id' => 'r3', 'timeLabel' => '18:00', 'label' => 'Tasks due tomorrow', 'frequencyLabel' => 'day before', 'channelsLabel' => 'telegram', 'isActive' => true],
            ['id' => 'r4', 'timeLabel' => '10:00', 'label' => 'Weekly review', 'frequencyLabel' => 'sunday', 'channelsLabel' => 'telegram · email', 'isActive' => true],
            ['id' => 'r5', 'timeLabel' => '09:00', 'label' => 'Goals off pace', 'frequencyLabel' => 'monday', 'channelsLabel' => 'email', 'isActive' => false],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function focusProject(): array
    {
        return [
            'goalId' => 'nomad-visa',
            'title' => 'Nomad visa',
            'color' => 'amber',
            'doneCount' => 9,
            'totalCount' => 20,
            'percent' => 45,
            'columns' => [
                [
                    'status' => 'todo',
                    'hiddenCount' => 6,
                    'cards' => [
                        ['id' => 'k1', 'title' => 'Health insurance, no co-pay', 'checklistDone' => 0, 'checklistTotal' => 3, 'dueLabel' => '24 oct', 'isOverdue' => false],
                        ['id' => 'k2', 'title' => 'Bank statements · 3 months', 'checklistDone' => 1, 'checklistTotal' => 3, 'dueLabel' => '22 oct', 'isOverdue' => true],
                        ['id' => 'k3', 'title' => 'Apostille diploma', 'checklistDone' => 0, 'checklistTotal' => 2, 'dueLabel' => '20 oct', 'isOverdue' => true],
                    ],
                ],
                [
                    'status' => 'doing',
                    'hiddenCount' => 0,
                    'cards' => [
                        ['id' => 'k4', 'title' => 'Sworn translation', 'checklistDone' => 2, 'checklistTotal' => 5, 'dueLabel' => '28 oct', 'isOverdue' => false],
                        ['id' => 'k5', 'title' => 'Apostilled marriage certificate', 'checklistDone' => 1, 'checklistTotal' => 2, 'dueLabel' => '30 oct', 'isOverdue' => false],
                    ],
                ],
                [
                    'status' => 'done',
                    'hiddenCount' => 6,
                    'cards' => [
                        ['id' => 'k6', 'title' => 'Passport · certified copy', 'checklistDone' => 2, 'checklistTotal' => 2, 'dueLabel' => '05 oct', 'isOverdue' => false],
                        ['id' => 'k7', 'title' => 'Remote work contract', 'checklistDone' => 3, 'checklistTotal' => 3, 'dueLabel' => '10 oct', 'isOverdue' => false],
                        ['id' => 'k8', 'title' => 'Employer letter', 'checklistDone' => 1, 'checklistTotal' => 1, 'dueLabel' => '12 oct', 'isOverdue' => false],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function log(): array
    {
        return [
            ['id' => 'l1', 'timeLabel' => '13:10', 'kind' => 'done', 'text' => 'log meals · diet', 'trailing' => '+2'],
            ['id' => 'l2', 'timeLabel' => '12:55', 'kind' => 'done', 'text' => 'upper body · training', 'trailing' => '+3'],
            ['id' => 'l3', 'timeLabel' => '07:42', 'kind' => 'done', 'text' => 'vocabulary · ielts', 'trailing' => '+1'],
            ['id' => 'l4', 'timeLabel' => '07:31', 'kind' => 'done', 'text' => 'speaking 30 min · ielts', 'trailing' => '+3'],
            ['id' => 'l5', 'timeLabel' => '07:30', 'kind' => 'sent', 'text' => 'daily brief sent · telegram', 'trailing' => null],
            ['id' => 'l6', 'timeLabel' => 'yday 21:34', 'kind' => 'checkin', 'text' => 'check-in · target hit', 'trailing' => '15/14'],
            ['id' => 'l7', 'timeLabel' => 'yday 18:00', 'kind' => 'warning', 'text' => 'bank statement due tomorrow · visa', 'trailing' => null],
        ];
    }
}
