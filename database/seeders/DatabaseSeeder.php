<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Services\Reminders\ReminderService;
use Illuminate\Database\Seeder;
use RuntimeException;

final class DatabaseSeeder extends Seeder
{
    public function run(ReminderService $reminders): void
    {
        $email = config('trakk.owner.email');
        $password = config('trakk.owner.password');

        if (! is_string($email) || ! is_string($password) || $email === '' || $password === '') {
            throw new RuntimeException('Set OWNER_EMAIL and OWNER_PASSWORD in .env before seeding.');
        }

        $owner = User::query()->updateOrCreate(
            ['email' => $email],
            ['name' => 'Owner', 'password' => $password],
        );

        $reminders->createDefaults($owner);

        if (app()->environment('local', 'testing')) {
            $this->call(DemoSeeder::class);
        }
    }
}
