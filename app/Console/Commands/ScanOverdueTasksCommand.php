<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Tasks\OverdueScanService;
use App\Support\Clock;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('tasks:scan-overdue')]
#[Description('Log a warning for every task that became overdue')]
final class ScanOverdueTasksCommand extends Command
{
    public function handle(OverdueScanService $scan, Clock $clock): int
    {
        $logged = User::query()->get()->sum(fn (User $user): int => $scan->scan($user, $clock->today($user)));

        $this->components->info("Logged {$logged} overdue ".($logged === 1 ? 'warning' : 'warnings').'.');

        return self::SUCCESS;
    }
}
