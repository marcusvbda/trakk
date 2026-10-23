<?php

declare(strict_types=1);

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
        ->assertSee('Digital nomad visa · Spain')
        ->assertSee('9/20')
        ->screenshot(filename: "goal-{$name}");
})->with('viewports');

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
