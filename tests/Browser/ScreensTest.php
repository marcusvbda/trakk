<?php

declare(strict_types=1);

use App\Models\User;
use Pest\Browser\Api\AwaitableWebpage;

const VIEWPORTS = [
    'desktop' => [1440, 900],
    'mobile' => [390, 844],
];

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

foreach (VIEWPORTS as $name => [$width, $height]) {
    it("captures the sign in screen on {$name}", function () use ($name, $width, $height) {
        visit('/login')
            ->resize($width, $height)
            ->wait(1)
            ->screenshot(filename: "login-{$name}");
    });

    it("captures the sign in error state on {$name}", function () use ($name, $width, $height) {
        visit('/login')
            ->resize($width, $height)
            ->press('sign in')
            ->wait(0.8)
            ->assertSee('email and password are required')
            ->screenshot(filename: "login-error-{$name}");
    });

    it("captures the dashboard on {$name}", function () use ($name, $width, $height) {
        signIn($width, $height)
            ->assertPathIs('/')
            ->screenshot(filename: "dashboard-{$name}");
    });

    it("captures the goal detail on {$name}", function () use ($name, $width, $height) {
        signIn($width, $height)
            ->navigate('/goals/nomad-visa')
            ->wait(1)
            ->screenshot(filename: "goal-{$name}");
    });
}
