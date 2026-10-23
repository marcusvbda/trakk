<?php

declare(strict_types=1);

namespace App\Support\Mocks;

/**
 * Prototype data shaped like the future goal detail Resources. Replaced by the M4 read model.
 */
final class GoalDetailMock
{
    /**
     * @return array<string, mixed>
     */
    public static function props(): array
    {
        return [
            'goal' => [
                'id' => 'nomad-visa',
                'slug' => 'nomad-visa',
                'title' => 'Digital nomad visa · Spain',
                'type' => 'project',
                'priority' => 'high',
                'color' => 'amber',
                'colorHex' => '#F2B03D',
                'pace' => ['status' => 'behind', 'delta' => -13],
                'facts' => [
                    'start' => '01 jul 2026',
                    'dueDate' => '15 jan 2027',
                    'remainingDays' => 84,
                    'frequency' => null,
                ],
                'objective' => [
                    'text' => 'Gather, translate and apostille every document the digital nomad visa requires, and file the application before the deadline.',
                    'doneWhen' => 'Application filed with every document accepted.',
                    'area' => 'relocation',
                    'linkedTo' => 'moving fund',
                ],
            ],
            'progress' => [
                'percent' => 45,
                'expectedPercent' => 58,
                'doneCount' => 9,
                'totalCount' => 20,
            ],
            'paceChart' => [
                'totalDays' => 198,
                'elapsedDays' => 114,
                'startLabel' => '01 jul',
                'dueLabel' => '15 jan',
                'ticks' => [
                    ['day' => 60.5, 'label' => 'sep'],
                    ['day' => 121, 'label' => 'nov'],
                ],
                'actual' => [
                    ['day' => 0, 'percent' => 0],
                    ['day' => 28, 'percent' => 10],
                    ['day' => 56, 'percent' => 20],
                    ['day' => 84, 'percent' => 35],
                    ['day' => 114, 'percent' => 45],
                ],
                'expectedPercent' => 58,
                'projectedPercent' => 78,
                'currentPace' => 0.6,
                'requiredPace' => 0.9,
            ],
            'board' => [
                'columns' => [
                    ['status' => 'todo', 'cards' => self::todoCards()],
                    ['status' => 'doing', 'cards' => self::doingCards()],
                    ['status' => 'done', 'cards' => self::doneCards()],
                ],
            ],
            'reminders' => [
                ['id' => 'r1', 'whenLabel' => 'day before', 'label' => 'Task due tomorrow alert', 'detailLabel' => '18:00'],
                ['id' => 'r2', 'whenLabel' => 'monday', 'label' => 'Pace summary for this goal', 'detailLabel' => '09:00'],
                ['id' => 'r3', 'whenLabel' => '01 jan', 'label' => 'Alert: 14 days to deadline', 'detailLabel' => 'once'],
            ],
            'history' => [
                ['id' => 'h1', 'timeLabel' => '22 oct', 'kind' => 'warning', 'text' => 'bank statements overdue', 'trailing' => null],
                ['id' => 'h2', 'timeLabel' => '20 oct', 'kind' => 'warning', 'text' => 'apostille diploma overdue', 'trailing' => null],
                ['id' => 'h3', 'timeLabel' => '14 oct', 'kind' => 'done', 'text' => 'proof of employment completed', 'trailing' => null],
                ['id' => 'h4', 'timeLabel' => '12 oct', 'kind' => 'done', 'text' => 'employer letter completed', 'trailing' => null],
                ['id' => 'h5', 'timeLabel' => '10 oct', 'kind' => 'sent', 'text' => 'sworn translation → doing', 'trailing' => null],
                ['id' => 'h6', 'timeLabel' => '10 oct', 'kind' => 'done', 'text' => 'remote work contract completed', 'trailing' => null],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function todoCards(): array
    {
        return [
            self::card('t1', 'Health insurance, no co-pay', '24 oct', 0, 3, tag: 'critical'),
            self::card('t2', 'Bank statements · 3 months', '22 oct', 1, 3, isOverdue: true),
            self::card('t3', 'Apostille diploma', '20 oct', 0, 2, isOverdue: true),
            self::card('t4', 'Apostilled criminal record', '05 nov', 0, 2),
            self::card('t5', 'Application form filled in', '10 nov', 0, 1),
            self::card('t6', 'Proof of income · 3 months', '12 nov', 1, 3),
            self::card('t7', 'Pay application fee', '15 nov', 0, 1),
            self::card('t8', 'Book filing appointment', '20 nov', 0, 1, tag: 'milestone'),
            self::card('t9', 'Photos in required format', '25 nov', 0, 1),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function doingCards(): array
    {
        return [
            self::card('t10', 'Sworn translation', '28 oct', 2, 5),
            self::card('t11', 'Apostilled marriage certificate', '30 oct', 1, 2),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function doneCards(): array
    {
        return [
            self::card('t12', 'Passport · certified copy', '05 oct', 2, 2),
            self::card('t13', 'Remote work contract', '10 oct', 3, 3),
            self::card('t14', 'Employer letter', '12 oct', 1, 1),
            self::card('t15', 'Proof of 3+ months employment', '14 oct', 1, 1),
            self::card('t16', 'Scanned diploma', '20 sep', 1, 1),
            self::card('t17', 'Updated CV', '15 sep', 1, 1),
            self::card('t18', 'Official document checklist', '01 sep', 1, 1),
            self::card('t19', 'Research requirements and deadlines', '20 aug', 1, 1),
            self::card('t20', 'Process cost spreadsheet', '10 aug', 1, 1),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function card(string $id, string $title, string $dueLabel, int $checklistDone, int $checklistTotal, bool $isOverdue = false, ?string $tag = null): array
    {
        return [
            'id' => $id,
            'title' => $title,
            'dueLabel' => $dueLabel,
            'checklistDone' => $checklistDone,
            'checklistTotal' => $checklistTotal,
            'isOverdue' => $isOverdue,
            'tag' => $tag,
        ];
    }
}
