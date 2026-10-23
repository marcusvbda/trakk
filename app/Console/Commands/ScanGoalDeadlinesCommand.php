<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Goals\DeadlineScanService;
use App\Support\Clock;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('goals:scan-deadlines')]
#[Description('Log a warning for goals 14 and 3 days before their due date')]
final class ScanGoalDeadlinesCommand extends Command
{
    public function handle(DeadlineScanService $scan, Clock $clock): int
    {
        $logged = User::query()->get()->sum(fn (User $user): int => $scan->scan($user, $clock->today($user)));

        $this->components->info("Logged {$logged} deadline ".($logged === 1 ? 'warning' : 'warnings').'.');

        return self::SUCCESS;
    }
}
