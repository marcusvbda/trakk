<?php

declare(strict_types=1);

use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

describe('with the demo data', function () {
    beforeEach(function () {
        $this->owner = seedDemoOwner();
    });

    it('renders the six KPIs from the real numbers', function () {
        $this->actingAs($this->owner)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->has('kpis', 6)
                ->where('kpis.0.value', '9')
                ->where('kpis.0.suffix', '/14')
                ->where('kpis.0.sub', '64% of daily target')
                ->where('kpis.1.value', '12')
                ->where('kpis.1.sub', 'record · 21 days')
                ->where('kpis.3.value', '4')
                ->where('kpis.3.suffix', '/6')
                ->where('kpis.3.sub', '2 off pace')
                ->where('kpis.4.value', '4')
                ->where('kpis.4.sub', '2 from nomad visa'));
    });

    it('lists today, the overdue tasks, the suggestion and the goals', function () {
        $this->actingAs($this->owner)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('actions', 8)
                ->where('actions.0.title', 'Speaking · 30 min')
                ->where('actions.0.isDone', true)
                ->has('overdue', 4)
                ->where('overdue.0.daysLate', 5)
                ->where('suggestion', 'knock out the 2 overdue nomad visa tasks today. It unblocks your most behind goal and clears 2 items from red.')
                ->has('goals', 6)
                ->where('goalsSummary', ['active' => 6, 'onPace' => 4])
                ->where('firstRun', null));
    });

    it('shows the most behind project as the focus board with three cards per column', function () {
        $this->actingAs($this->owner)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('focusProject.goalSlug', 'nomad-visa')
                ->where('focusProject.doneCount', 9)
                ->where('focusProject.totalCount', 20)
                ->has('focusProject.columns', 3)
                ->has('focusProject.columns.0.cards', 3)
                ->where('focusProject.columns.0.hiddenCount', 6)
                ->where('focusProject.columns.1.hiddenCount', 0));
    });

    it('returns the pace of each habit with its week dots and the points of the last 30 days', function () {
        $this->actingAs($this->owner)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('goals.1.slug', 'ielts')
                ->has('goals.1.week.days', 7)
                ->has('pointsChart.days', 30)
                ->where('pointsChart.target', 14)
                ->has('consistency.weeks', 12));
    });

    it('stays within 20 queries however many tasks there are', function () {
        $this->actingAs($this->owner);
        DB::enableQueryLog();

        $this->get(route('dashboard'))->assertOk();

        expect(count(DB::getQueryLog()))->toBeLessThanOrEqual(20);
    });

    it('shares the top bar with the real date and the mcp status', function () {
        $this->actingAs($this->owner)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('topBar.dateLabel', 'fri, 23 oct 2026 · wk 43 · day 296/365')
                ->where('topBar.yearProgress', 81)
                ->where('topBar.isMcpLive', false));
    });
});

describe('without any data', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
    });

    it('renders every panel empty instead of failing', function () {
        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('kpis.0.sub', 'first check of the day')
                ->where('kpis.1.sub', 'start one today')
                ->where('kpis.2.sub', 'no data last week')
                ->where('kpis.3.value', '—')
                ->where('kpis.3.sub', 'no active goals')
                ->where('kpis.4.sub', 'all clear')
                ->where('kpis.5.sub', 'no deadlines in 30d')
                ->where('actions', [])
                ->where('overdue', [])
                ->where('suggestion', null)
                ->where('upcoming', [])
                ->where('goals', [])
                ->where('reminders', [])
                ->where('focusProject', null)
                ->where('log', [])
                ->where('status.nextReminderAt', null)
                ->where('status.isTelegramLinked', false)
                ->has('pointsChart.days', 30)
                ->has('consistency.weeks', 12));
    });

    it('shows the first-run panel while the owner has no goals', function () {
        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('firstRun.isTelegramLinked', false));

        $this->user->update(['telegram_chat_id' => '123']);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('firstRun.isTelegramLinked', true));
    });
});

describe('the log', function () {
    beforeEach(function () {
        $this->travelTo(day('2026-10-23')->setTime(12, 0));
        $this->user = User::factory()->create();
    });

    it('shows the latest eight activities, newest first, with points and check-in scores', function () {
        foreach (range(1, 9) as $minute) {
            Activity::factory()->for($this->user)->create([
                'message' => "entry {$minute}",
                'points' => $minute,
                'occurred_at' => now()->subHours(10)->addMinutes($minute),
            ]);
        }
        Activity::factory()->for($this->user)->create([
            'type' => ActivityType::CheckIn,
            'message' => 'check-in · target hit',
            'meta' => ['score' => 15, 'target' => 14],
            'occurred_at' => now()->subMinutes(5),
        ]);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('log', 8)
                ->where('log.0.text', 'check-in · target hit')
                ->where('log.0.kind', 'checkin')
                ->where('log.0.trailing', '15/14')
                ->where('log.1.text', 'entry 9')
                ->where('log.1.trailing', '+9')
                ->where('log.7.text', 'entry 3'));
    });

    it('never shows another user\'s activities', function () {
        Activity::factory()->create(['message' => 'someone else']);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('log', []));
    });
});

describe('the first-run panel', function () {
    it('stays away once the owner has any goal, even a paused one', function () {
        $user = User::factory()->create();
        Goal::factory()->for($user)->paused()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('firstRun', null)->where('goals', []));
    });
});
