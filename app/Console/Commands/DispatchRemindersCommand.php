<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SendReminder;
use App\Services\Reminders\ReminderService;
use App\Support\Clock;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('reminders:dispatch')]
#[Description('Queue the reminders whose time has come, one job per channel')]
final class DispatchRemindersCommand extends Command
{
    public function handle(ReminderService $reminders, Clock $clock): int
    {
        $count = 0;

        foreach ($reminders->due($clock->now()) as $due) {
            foreach ($due->reminder->channels as $channel) {
                SendReminder::dispatch($due->reminder, $channel, $due->slot);
                $count++;
            }
        }

        $this->components->info("Queued {$count} reminder ".($count === 1 ? 'message' : 'messages').'.');

        return self::SUCCESS;
    }
}
