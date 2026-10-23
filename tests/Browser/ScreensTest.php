<?php

declare(strict_types=1);

use App\Models\User;
use Pest\Browser\Api\AwaitableWebpage;

function signIn(int $width, int $height): AwaitableWebpage
{
    $user = User::factory()->create();

    return visit('/login')
        ->resize($width, $height)
        ->fill('email', $user->email)
        ->fill('password', 'password')
        ->press('sign in')
        ->wait(1.5);
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

it('shows the goal detail on {name}', function (string $name, int $width, int $height) {
    signIn($width, $height)
        ->navigate('/goals/nomad-visa')
        ->wait(1)
        ->assertSee('Digital nomad visa · Spain')
        ->assertSee('9/20')
        ->screenshot(filename: "goal-{$name}");
})->with('viewports');
