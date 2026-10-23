<?php

declare(strict_types=1);

use App\Services\Dashboard\SuggestionService;
use Illuminate\Support\Collection;

describe('suggest', function () {
    it('names the most behind goal with overdue tasks and the number of tasks', function () {
        $behind = goalWithOverdue('Nomad visa · Spain', [3, 1]);
        $ahead = goalWithOverdue('Read 12 books', [5], doneTasks: 9);

        $text = app(SuggestionService::class)->suggest(Collection::make([$ahead, $behind]), day('2026-10-23'));

        expect($text)->toBe('knock out the 2 overdue nomad visa tasks today. It unblocks your most behind goal and clears 2 items from red.');
    });

    it('uses the singular for one task', function () {
        $goal = goalWithOverdue('Diet', [2]);

        expect(app(SuggestionService::class)->suggest(Collection::make([$goal]), day('2026-10-23')))
            ->toBe('knock out the 1 overdue diet task today. It unblocks your most behind goal and clears 1 item from red.');
    });

    it('moves a long list to Saturday morning on weekdays and to today on weekends', function (string $date, string $when) {
        $goal = goalWithOverdue('Nomad visa', [4, 3, 2]);

        expect(app(SuggestionService::class)->suggest(Collection::make([$goal]), day($date)))
            ->toContain("tasks {$when}.");
    })->with([
        'a friday' => ['2026-10-23', 'on Saturday morning'],
        'a saturday' => ['2026-10-24', 'today'],
        'a sunday' => ['2026-10-25', 'today'],
    ]);

    it('has nothing to say when no task is overdue', function () {
        $goal = goalWithOverdue('Nomad visa', [], doneTasks: 2);

        expect(app(SuggestionService::class)->suggest(Collection::make([$goal]), day('2026-10-23')))->toBeNull();
    });
});
