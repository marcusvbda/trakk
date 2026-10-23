<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\DatabaseSeeder;

describe('DatabaseSeeder', function () {
    it('creates the owner who can log in with the configured credentials', function () {
        config(['trakk.owner.email' => 'owner@example.com', 'trakk.owner.password' => 'secret-pass-123']);

        $this->seed(DatabaseSeeder::class);

        $this->post(route('login.store'), ['email' => 'owner@example.com', 'password' => 'secret-pass-123'])
            ->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();
    });

    it('does not duplicate the owner when run twice', function () {
        config(['trakk.owner.email' => 'owner@example.com', 'trakk.owner.password' => 'secret-pass-123']);

        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        expect(User::query()->count())->toBe(1);
    });

    it('fails when the owner credentials are missing', function () {
        config(['trakk.owner.email' => null, 'trakk.owner.password' => null]);

        $this->seed(DatabaseSeeder::class);
    })->throws(RuntimeException::class, 'OWNER_EMAIL');
});
