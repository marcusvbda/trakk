<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Data\ReminderData;
use App\Enums\ActivitySource;
use App\Enums\ActivityType;
use App\Enums\GoalColor;
use App\Enums\GoalType;
use App\Enums\Priority;
use App\Enums\ReminderChannel;
use App\Enums\ReminderKind;
use App\Enums\TaskStatus;
use App\Models\Activity;
use App\Models\ChecklistItem;
use App\Models\Completion;
use App\Models\Goal;
use App\Models\Task;
use App\Models\User;
use App\Services\Activity\ActivityLogger;
use App\Services\Activity\CheckInService;
use App\Services\Reminders\ReminderService;
use App\Support\Clock;
use Carbon\CarbonImmutable;
use Database\Seeders\Demo\DemoHistory;
use Illuminate\Database\Seeder;

/**
 * The prototype's data, with every date counted from today so the screens always look like the reference
 * images. Habit actions run every day except today's weekday for the extras, so Today lists exactly the
 * prototype's eight actions whatever day this runs.
 */
final class DemoSeeder extends Seeder
{
    private const array EVERY_DAY = [1, 2, 3, 4, 5, 6, 7];

    private const array DONE_TODAY = ['Speaking · 30 min', 'Vocabulary · 20 words', 'Upper body workout', 'Log meals'];

    private CarbonImmutable $today;

    private User $owner;

    public function run(Clock $clock, ReminderService $reminders, ActivityLogger $activity, CheckInService $checkIn, DemoHistory $history): void
    {
        $this->owner = User::query()->where('email', config('trakk.owner.email'))->firstOrFail();

        if (app()->isProduction() || $this->owner->goals()->exists()) {
            return;
        }

        $this->today = $clock->today($this->owner);

        $visa = $this->visa();
        $ielts = $this->habit(2, 'ielts', 'IELTS 7.0', GoalColor::Blue, Priority::High, 5, -69, 148, 'Reach band 7.0 on the IELTS Academic test.', 'Test taken with an overall band of 7.0 or higher.', 'language');
        $training = $this->habit(3, 'training', 'Train 4x a week', GoalColor::Cyan, Priority::Medium, 4, -52, 69, 'Build a steady training routine.', 'Four sessions a week for the whole window.', 'health');
        $diet = $this->habit(4, 'diet', 'Diet · calorie deficit', GoalColor::Pink, Priority::Medium, 5, -38, 69, 'Stay in a calorie deficit until the year ends.', 'Target weight reached.', 'health');
        $fund = $this->fund($visa);
        $books = $this->habit(6, 'books', 'Read 12 books in 2026', GoalColor::Sand, Priority::Low, 7, -295, 69, 'Read twelve books this year.', 'Twelve books finished.', 'reading');
        $visa->update(['related_goal_id' => $fund->id]);

        $habitTasks = [
            ...$this->habitActions($ielts, [['Speaking · 30 min', 3, '07:00', true], ['Vocabulary · 20 words', 1, '07:40', true], ['Listening practice', 2, '19:00', false], ['Writing task', 3, '20:00', false]]),
            ...$this->habitActions($training, [['Upper body workout', 3, '12:30', true], ['Stretching · 10 min', 1, '21:00', false]]),
            ...$this->habitActions($diet, [['Log meals', 2, null, true], ['Water · 2 L', 1, null, false]]),
            ...$this->habitActions($books, [['Reading · 20 pages', 1, '22:00', true], ['Long read · 30 min', 2, '21:30', false]]),
        ];
        $this->habitOneOffs($ielts, $diet, $books);

        $rows = $history->rows(
            $habitTasks,
            [$ielts->id => $ielts, $training->id => $training, $diet->id => $diet, $books->id => $books],
            [$ielts->id => 34, $training->id => 45, $diet->id => 42, $books->id => 75],
            [$books->id, $training->id, null, $diet->id, null, $books->id, null],
            self::DONE_TODAY,
            $this->today,
            $this->owner->id,
            $this->owner->daily_points_target,
        );
        foreach (array_chunk($rows, 500) as $chunk) {
            Completion::query()->insert($chunk);
        }

        $reminders->createDefaults($this->owner);
        $this->goalReminders($reminders, $visa);
        $this->log($activity, $checkIn, $visa);
    }

    private function visa(): Goal
    {
        $visa = $this->goal(1, 'nomad-visa', 'Nomad visa · Spain', GoalType::Project, GoalColor::Amber, Priority::High, null, -114, 84, [
            'description' => 'Gather, translate and apostille every document the digital nomad visa requires, and file the application before the deadline.',
            'done_criteria' => 'Application filed with every document accepted.',
            'area' => 'relocation',
        ]);

        // [title, status, days from today the task is due, checklist done, checklist total, points, tag]
        $tasks = [
            ['Health insurance, no co-pay', TaskStatus::Todo, 1, 0, 3, 3, 'critical'],
            ['Bank statements · 3 months', TaskStatus::Todo, -1, 1, 3, 2, null],
            ['Apostille diploma', TaskStatus::Todo, -3, 0, 2, 3, null],
            ['Apostilled criminal record', TaskStatus::Todo, 13, 0, 2, 3, null],
            ['Book sworn translation', TaskStatus::Todo, 0, 0, 1, 4, null],
            ['Request apostilled marriage certificate', TaskStatus::Todo, 0, 0, 1, 3, null],
            ['Pay application fee', TaskStatus::Todo, 23, 0, 1, 1, null],
            ['Book filing appointment', TaskStatus::Todo, 28, 0, 1, 2, 'milestone'],
            ['Photos in required format', TaskStatus::Todo, 33, 0, 1, 1, null],
            ['Sworn translation', TaskStatus::Doing, 5, 2, 5, 4, null],
            ['Apostilled marriage certificate', TaskStatus::Doing, 7, 1, 2, 3, null],
            ['Passport · certified copy', TaskStatus::Done, -18, 2, 2, 1, null],
            ['Remote work contract', TaskStatus::Done, -13, 3, 3, 3, null],
            ['Employer letter', TaskStatus::Done, -11, 1, 1, 1, null],
            ['Proof of 3+ months employment', TaskStatus::Done, -9, 1, 1, 2, null],
            ['Scanned diploma', TaskStatus::Done, -33, 1, 1, 1, null],
            ['Updated CV', TaskStatus::Done, -38, 1, 1, 1, null],
            ['Official document checklist', TaskStatus::Done, -52, 1, 1, 1, null],
            ['Research requirements and deadlines', TaskStatus::Done, -64, 1, 1, 1, null],
            ['Process cost spreadsheet', TaskStatus::Done, -74, 1, 1, 1, null],
        ];

        foreach ($tasks as $position => [$title, $status, $dueOffset, $checkedItems, $items, $points, $tag]) {
            $due = $this->today->addDays($dueOffset);
            $task = Task::factory()->for($visa)->create([
                'title' => $title,
                'status' => $status,
                'due_date' => $due,
                'points' => $points,
                'tag' => $tag,
                'position' => $position + 1,
                'completed_at' => $status === TaskStatus::Done ? $this->at($due, '10:00') : null,
            ]);

            $this->checklist($task, $checkedItems, $items);

            if ($status === TaskStatus::Done) {
                Completion::factory()->forTask($task)->on($due)->create();
            }
        }

        return $visa;
    }

    private function fund(Goal $visa): Goal
    {
        $fund = $this->goal(5, 'moving-fund', 'Moving fund', GoalType::Project, GoalColor::Violet, Priority::Medium, null, -83, 128, [
            'description' => 'Save enough to cover the first six months abroad.',
            'done_criteria' => 'All six deposits made.',
            'area' => 'relocation',
        ]);

        foreach ([-55, -25, 5, 35, 65, 95] as $index => $dueOffset) {
            $due = $this->today->addDays($dueOffset);
            $isDone = $index < 3;
            $task = Task::factory()->for($fund)->create([
                'title' => 'Deposit '.($index + 1).' of 6',
                'status' => $isDone ? TaskStatus::Done : TaskStatus::Todo,
                'due_date' => $due,
                'points' => 2,
                'position' => $index + 1,
                'completed_at' => $isDone ? $this->at($index === 2 ? $this->today->subDays(2) : $due, '09:00') : null,
            ]);

            if ($isDone) {
                Completion::factory()->forTask($task)->on($index === 2 ? $this->today->subDays(2) : $due)->create();
            }
        }

        Task::factory()->for($fund)->doing()->create(['title' => 'Review moving budget', 'points' => 2, 'position' => 7]);

        return $fund;
    }

    private function habit(int $position, string $slug, string $title, GoalColor $color, Priority $priority, int $perWeek, int $startOffset, int $dueOffset, string $description, string $doneCriteria, string $area): Goal
    {
        return $this->goal($position, $slug, $title, GoalType::Habit, $color, $priority, $perWeek, $startOffset, $dueOffset, [
            'description' => $description,
            'done_criteria' => $doneCriteria,
            'area' => $area,
        ]);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function goal(int $position, string $slug, string $title, GoalType $type, GoalColor $color, Priority $priority, ?int $perWeek, int $startOffset, int $dueOffset, array $extra): Goal
    {
        return Goal::factory()->for($this->owner)->create([
            ...$extra,
            'position' => $position,
            'slug' => $slug,
            'title' => $title,
            'type' => $type,
            'color' => $color,
            'priority' => $priority,
            'target_per_week' => $perWeek,
            'start_date' => $this->today->addDays($startOffset),
            'due_date' => $this->today->addDays($dueOffset),
        ]);
    }

    /**
     * Daily actions, and extras on every weekday but today's.
     *
     * @param  list<array{string, int, string|null, bool}>  $actions  title, points, time, daily
     * @return list<Task>
     */
    private function habitActions(Goal $goal, array $actions): array
    {
        $extraDays = array_values(array_diff(self::EVERY_DAY, [$this->today->dayOfWeekIso]));

        return array_map(fn (array $action): Task => Task::factory()->for($goal)->create([
            'title' => $action[0],
            'points' => $action[1],
            'scheduled_time' => $action[2],
            'days_of_week' => $action[3] ? self::EVERY_DAY : $extraDays,
        ]), $actions);
    }

    private function habitOneOffs(Goal $ielts, Goal $diet, Goal $books): void
    {
        Task::factory()->for($ielts)->overdueBy(2, $this->today)->points(3)->create(['title' => 'Writing mock test']);
        Task::factory()->for($ielts)->dueOn($this->today->addDays(3))->points(4)->create(['title' => 'Full mock test']);
        Task::factory()->for($diet)->dueOn($this->today->addDays(4))->points(1)->create(['title' => 'Weekly weigh-in']);
        Task::factory()->for($books)->overdueBy(5, $this->today)->points(2)->create(['title' => 'Read chapters 8–10']);
    }

    private function checklist(Task $task, int $checked, int $total): void
    {
        for ($index = 0; $index < $total; $index++) {
            ChecklistItem::factory()->for($task)->create([
                'title' => 'Step '.($index + 1),
                'is_done' => $index < $checked,
                'position' => $index + 1,
            ]);
        }
    }

    private function goalReminders(ReminderService $reminders, Goal $visa): void
    {
        $reminders->create($this->owner, new ReminderData(ReminderKind::DueTomorrow, 'Task due tomorrow alert', '18:00', [ReminderChannel::Telegram], [1, 2, 3, 4, 5, 6, 7], goalId: $visa->id));
        $reminders->create($this->owner, new ReminderData(ReminderKind::PaceSummary, 'Pace summary for this goal', '09:00', [ReminderChannel::Telegram], [1], goalId: $visa->id));
        $reminders->create($this->owner, new ReminderData(ReminderKind::DeadlineAlert, 'Alert: 14 days to deadline', '09:00', [ReminderChannel::Telegram], fireOn: $visa->due_date->subDays(14), goalId: $visa->id));
    }

    private function log(ActivityLogger $activity, CheckInService $checkIn, Goal $visa): void
    {
        $this->logCompletions($activity, [
            'Log meals' => '13:10',
            'Upper body workout' => '12:55',
            'Vocabulary · 20 words' => '07:42',
            'Speaking · 30 min' => '07:31',
        ]);

        $this->logVisaHistory($activity, $visa);

        $this->activityAt(ActivityType::ReminderSent, 'daily brief sent · telegram', $this->today, '07:30');
        $this->activityAt(ActivityType::DeadlineNear, 'bank statements due tomorrow · nomad visa', $this->today->subDay(), '18:00', $visa->id);
        $checkIn->closeDay($this->owner, $this->today->subDay(), ActivitySource::Web)
            ->update(['occurred_at' => $this->at($this->today->subDay(), '21:34')]);
    }

    /**
     * @param  array<string, string>  $times  task title => time of day it was checked today
     */
    private function logCompletions(ActivityLogger $activity, array $times): void
    {
        foreach ($times as $title => $time) {
            $task = Task::query()->with('goal')->where('title', $title)->whereIn('goal_id', $this->owner->goals()->select('id'))->firstOrFail();
            $completion = $task->completions()->where('completed_on', $this->today->toDateString())->firstOrFail();

            $activity->taskCompleted($task, $completion)->update(['occurred_at' => $this->at($this->today, $time)]);
        }
    }

    private function logVisaHistory(ActivityLogger $activity, Goal $visa): void
    {
        $visa->load('tasks');

        foreach ($visa->tasks->where('status', TaskStatus::Done)->filter(fn (Task $task): bool => $task->due_date->greaterThan($this->today->subDays(20))) as $task) {
            $completion = $task->completions()->firstOrFail();
            $activity->taskCompleted($task, $completion)->update(['occurred_at' => $this->at($task->due_date, '10:00')]);
        }

        $this->activityAt(ActivityType::TaskOverdue, 'bank statements overdue', $this->today->subDay(), '00:05', $visa->id);
        $this->activityAt(ActivityType::TaskOverdue, 'apostille diploma overdue', $this->today->subDays(3), '00:05', $visa->id);
        $this->activityAt(ActivityType::TaskMoved, 'sworn translation → doing', $this->today->subDays(13), '09:00', $visa->id);
    }

    private function activityAt(ActivityType $type, string $message, CarbonImmutable $day, string $time, ?int $goalId = null): void
    {
        Activity::factory()->create([
            'user_id' => $this->owner->id,
            'goal_id' => $goalId,
            'type' => $type,
            'message' => $message,
            'occurred_at' => $this->at($day, $time),
        ]);
    }

    private function at(CarbonImmutable $day, string $time): CarbonImmutable
    {
        return CarbonImmutable::parse($day->toDateString().' '.$time, $this->owner->timezone)->utc();
    }
}
