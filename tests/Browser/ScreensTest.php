<?php

declare(strict_types=1);

use App\Models\Goal;
use App\Models\Task;
use App\Models\User;
use Pest\Browser\Api\AwaitableWebpage;

function signInAs(User $user, string $password, int $width, int $height): AwaitableWebpage
{
    return visit('/login')
        ->resize($width, $height)
        ->fill('email', $user->email)
        ->fill('password', $password)
        ->press('sign in')
        ->wait(1.5);
}

function signIn(int $width, int $height): AwaitableWebpage
{
    return signInAs(seedDemoOwner(), 'secret-pass-123', $width, $height);
}

dataset('viewports', [
    'desktop' => ['desktop', 1440, 900],
    'mobile' => ['mobile', 390, 844],
]);

it('shows the sign in screen on {name}', function (string $name, int $width, int $height) {
    visit('/login')
        ->resize($width, $height)
        ->wait(1)
        ->assertSee('goals · pace · focus')
        ->screenshot(filename: "login-{$name}");
})->with('viewports');

it('shows the sign in error state on {name}', function (string $name, int $width, int $height) {
    visit('/login')
        ->resize($width, $height)
        ->press('sign in')
        ->wait(0.8)
        ->assertSee('email and password are required')
        ->screenshot(filename: "login-error-{$name}");
})->with('viewports');

it('shows the dashboard after signing in on {name}', function (string $name, int $width, int $height) {
    signIn($width, $height)
        ->assertPathIs('/')
        ->assertSee('Focus project')
        ->assertSee('4/8 actions')
        ->screenshot(filename: "dashboard-{$name}");
})->with('viewports');

it('checks an action and the server keeps it', function () {
    signIn(1440, 900)
        ->assertSee('4/8 actions')
        ->click('Book sworn translation')
        ->wait(1.5)
        ->assertSee('5/8 actions')
        ->refresh()
        ->wait(1)
        ->assertSee('5/8 actions');
});

it('opens the replan sheet with the overdue tasks and moves them', function () {
    signIn(1440, 900)
        ->click('replan')
        ->wait(0.8)
        ->assertSee('move 4 tasks')
        ->screenshot(filename: 'replan-sheet')
        ->click('move 4 tasks')
        ->wait(1.5)
        ->assertSee('nothing overdue');
});

it('opens the check-in sheet and closes the day', function () {
    signIn(1440, 900)
        ->click('check-in')
        ->wait(0.8)
        ->assertSee('close the day')
        ->screenshot(filename: 'check-in-sheet')
        ->click('close the day')
        ->wait(1.5)
        ->assertSee('check-in · 5 pts short');
});

it('shows every empty state on the dashboard of a new account', function () {
    signInAs(User::factory()->create(), 'password', 1440, 900)
        ->assertPathIs('/')
        ->assertSee('trakk init')
        ->assertSee('no actions scheduled today')
        ->assertSee('nothing overdue')
        ->assertSee('no active goals')
        ->assertSee('no points yet')
        ->assertSee('your first good day lights the first cell')
        ->assertSee('no reminders')
        ->assertSee('no active project')
        ->assertSee('nothing logged yet')
        ->screenshot(filename: 'dashboard-empty-desktop');
});

it('shows the goal detail on {name}', function (string $name, int $width, int $height) {
    signIn($width, $height)
        ->navigate('/goals/nomad-visa')
        ->wait(1)
        ->assertSee('Nomad visa · Spain')
        ->assertSee('9/20')
        ->screenshot(filename: "goal-{$name}");
})->with('viewports');

it('shows every empty state on a goal with no data', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->project()->create([
        'title' => 'Bare goal',
        'slug' => 'bare-goal',
        'description' => null,
        'done_criteria' => null,
    ]);

    signInAs($user, 'password', 1440, 900)
        ->navigate('/goals/'.$goal->slug)
        ->wait(1)
        ->assertSee('no objective yet')
        ->assertSee('done when: not defined')
        ->assertSee('add tasks to measure progress')
        ->assertSee('add the first task')
        ->assertSee('no tasks yet')
        ->assertSee('no reminders for this goal')
        ->assertSee('no history yet')
        ->screenshot(filename: 'goal-empty-desktop');
});

it('shows the actions of a habit goal instead of a board', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->habit(5)->create(['title' => 'Speak Spanish', 'slug' => 'speak-spanish']);
    Task::factory()->for($goal)->recurring([1, 2, 3, 4, 5], '07:30')->create(['title' => 'Speaking · 30 min']);

    signInAs($user, 'password', 1440, 900)
        ->navigate('/goals/'.$goal->slug)
        ->wait(1)
        ->assertSee('Speaking · 30 min')
        ->assertSee('0/5 this week')
        ->assertDontSee('→ advance')
        ->screenshot(filename: 'goal-habit-desktop');
});

it('creates a goal from the sheet and opens its page', function () {
    signIn(1440, 900)
        ->click('button:has-text("+ goal")')
        ->wait(0.8)
        ->assertSee('New goal')
        ->screenshot(filename: 'goal-sheet-create')
        ->fill('#goal-title', 'Learn to swim')
        ->click('create goal')
        ->wait(1.5)
        ->assertPathIs('/goals/learn-to-swim')
        ->assertSee('Learn to swim')
        ->assertSee('no tasks yet');
});

it('shows the validation errors under the fields of the goal sheet', function () {
    signIn(1440, 900)
        ->click('button:has-text("+ goal")')
        ->wait(0.8)
        ->click('create goal')
        ->wait(1)
        ->assertSee('The title field is required.');
});

it('edits a goal from its page and keeps the change after a reload', function () {
    signIn(1440, 900)
        ->navigate('/goals/nomad-visa')
        ->wait(1)
        ->click('edit')
        ->wait(0.8)
        ->assertSee('Edit goal')
        ->screenshot(filename: 'goal-sheet-edit')
        ->fill('#goal-title', 'Nomad visa · Portugal')
        ->click('save')
        ->wait(1.5)
        ->refresh()
        ->wait(1)
        ->assertSee('Nomad visa · Portugal');
});

it('deletes a goal after typing its title', function () {
    signIn(1440, 900)
        ->navigate('/goals/nomad-visa')
        ->wait(1)
        ->click('edit')
        ->wait(0.8)
        ->click('delete goal')
        ->fill('#goal-delete', 'Nomad visa · Spain')
        ->screenshot(filename: 'goal-sheet-delete')
        ->click('button.border-danger-line')
        ->wait(1.5)
        ->assertPathIs('/');
});

it('lists every goal by status on the goals page', function () {
    signIn(1440, 900)
        ->navigate('/goals')
        ->wait(1)
        ->assertSee('Active')
        ->assertSee('no paused goals')
        ->assertSee('no done goals')
        ->assertSee('no abandoned goals')
        ->screenshot(filename: 'goals-list-desktop');
});

it('adds a task to a project from its page', function () {
    signIn(1440, 900)
        ->navigate('/goals/nomad-visa')
        ->wait(1)
        ->assertSee('9/20')
        ->click('button:has-text("+ task")')
        ->wait(0.8)
        ->assertSee('New task')
        ->screenshot(filename: 'task-sheet-create')
        ->fill('#task-title', 'Notarise the contract')
        ->click('add task')
        ->wait(1.5)
        ->assertSee('Notarise the contract')
        ->assertSee('9/21');
});

it('edits a card, ticks its checklist and keeps the change', function () {
    signIn(1440, 900)
        ->navigate('/goals/nomad-visa')
        ->wait(1)
        ->click('button[aria-label=\'Edit task "Sworn translation"\']')
        ->wait(0.8)
        ->assertSee('Edit task')
        ->assertSee('checklist')
        ->type('input[aria-label="Add checklist item"]', 'Call the office')
        ->keys('input[aria-label="Add checklist item"]', 'Enter')
        ->wait(1.2)
        ->assertSee('Call the office')
        ->screenshot(filename: 'task-sheet-edit')
        ->refresh()
        ->wait(1)
        ->assertSee('2/6 items');
});

it('adds a repeating action to a habit goal', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->habit(5)->create(['title' => 'Speak Spanish', 'slug' => 'speak-spanish']);

    signInAs($user, 'password', 1440, 900)
        ->navigate('/goals/'.$goal->slug)
        ->wait(1)
        ->assertSee('no actions yet')
        ->click('button:has-text("+ task") >> nth=0')
        ->wait(0.8)
        ->assertSee('repeats on')
        ->screenshot(filename: 'task-sheet-habit')
        ->fill('#task-title', 'Speaking · 30 min')
        ->click('add task')
        ->wait(1.5)
        ->assertSee('Speaking · 30 min')
        ->assertSee('0/5 this week');
});

it('asks for a goal first when a new account adds a task', function () {
    signInAs(User::factory()->create(), 'password', 1440, 900)
        ->click('button:has-text("+ task") >> nth=0')
        ->wait(0.8)
        ->assertSee('create a goal before adding tasks');
});

it('adds a reminder from the dashboard', function () {
    signIn(1440, 900)
        ->click('button[aria-label="Add reminder"]')
        ->wait(0.8)
        ->assertSee('New reminder')
        ->screenshot(filename: 'reminder-sheet-create')
        ->fill('#reminder-label', 'Stretch')
        ->click('add reminder')
        ->wait(1.5)
        ->assertSee('Stretch');
});

it('edits and then deletes a reminder', function () {
    signIn(1440, 900)
        ->click('button[aria-label=\'Edit reminder "Daily brief"\']')
        ->wait(0.8)
        ->assertSee('Edit reminder')
        ->screenshot(filename: 'reminder-sheet-edit')
        ->click('delete reminder')
        ->click('delete reminder')
        ->wait(1.5)
        ->assertMissing('button[aria-label=\'Edit reminder "Daily brief"\']')
        ->assertSee('reminder deleted');
});

it('opens the command palette, searches and shows the empty state', function () {
    signIn(1440, 900)
        ->click('button[aria-label="Search or run a command"]')
        ->wait(0.8)
        ->assertSee('new goal')
        ->assertSee('go to goals')
        ->screenshot(filename: 'command-palette')
        ->type('input[aria-label="Search or run a command"]', 'nomad')
        ->wait(0.5)
        ->assertSee('Nomad visa · Spain')
        ->assertDontSee('new goal')
        ->type('input[aria-label="Search or run a command"]', 'zzzz')
        ->wait(0.5)
        ->assertSee('no matches for')
        ->screenshot(filename: 'command-palette-empty');
});

it('runs a palette command that opens a sheet', function () {
    signIn(1440, 900)
        ->click('button[aria-label="Search or run a command"]')
        ->wait(0.8)
        ->type('input[aria-label="Search or run a command"]', 'new goal')
        ->keys('input[aria-label="Search or run a command"]', 'Enter')
        ->wait(1)
        ->assertSee('New goal');
});

it('saves the timezone, daily target and telegram link in settings', function () {
    signIn(1440, 900)
        ->navigate('/settings/profile')
        ->wait(1)
        ->assertSee('not linked')
        ->fill('#daily_points_target', '20')
        ->fill('#telegram_chat_id', '123456789')
        ->click('[data-test="update-profile-button"]')
        ->wait(1.2)
        ->refresh()
        ->wait(1)
        ->assertValue('#daily_points_target', '20')
        ->assertSee('linked')
        ->screenshot(filename: 'settings-profile');
});

it('signs out from the user menu in the top bar', function () {
    $page = signIn(1440, 900)
        ->click('button[aria-haspopup="menu"]')
        ->wait(0.5)
        ->assertSee('log out')
        ->screenshot(filename: 'user-menu');

    $page->click('[data-test="logout-button"]')
        ->wait(1)
        ->assertPathIs('/login');
});
