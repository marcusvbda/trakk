<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('redirects guests to the sign in page', function (string $uri) {
    $this->get($uri)->assertRedirect(route('login'));
})->with([
    'dashboard' => '/',
    'goal detail' => '/goals/nomad-visa',
]);

it('renders the dashboard for the owner', function () {
    $this->actingAs(seedDemoOwner())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->has('kpis', 6)
            ->has('goals', 6));
});

it('renders the goal detail for the owner', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('goals.show', 'nomad-visa'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('goals/show')
            ->where('goal.slug', 'nomad-visa')
            ->has('board.columns', 3));
});

it('returns 404 for an unknown goal', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('goals.show', 'unknown-goal'))
        ->assertNotFound();
});
