<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ReminderChannel;
use App\Exceptions\TelegramException;
use App\Mail\ReminderMail;
use App\Models\Reminder;
use App\Services\Activity\ActivityLogger;
use App\Services\Reminders\ReminderMessageBuilder;
use App\Services\Telegram\TelegramClient;
use App\Support\Clock;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends one reminder through one channel for one slot, so a failing channel never repeats the other.
 */
final class SendReminder implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public readonly Reminder $reminder,
        public readonly ReminderChannel $channel,
        public readonly CarbonImmutable $slot,
    ) {}

    public function uniqueId(): string
    {
        return $this->reminder->id.'-'.$this->channel->value.'-'.$this->slot->toIso8601String();
    }

    public function handle(ReminderMessageBuilder $messages, TelegramClient $telegram, ActivityLogger $activity, Clock $clock): void
    {
        $user = $this->reminder->loadMissing('user')->user;
        $text = $messages->build($this->reminder, $clock->today($user));

        match ($this->channel) {
            ReminderChannel::Telegram => $this->sendToTelegram($telegram, $user->telegram_chat_id, $text),
            ReminderChannel::Email => Mail::to($user)->send(new ReminderMail($this->reminder->label, $text)),
        };

        $this->reminder->update(['last_sent_at' => $clock->now(), 'last_error' => null]);
        $activity->reminderSent($this->reminder, $this->channel);
    }

    public function failed(Throwable $exception): void
    {
        $this->reminder->update(['last_error' => $exception->getMessage()]);

        app(ActivityLogger::class)->reminderFailed($this->reminder, $this->channel, $this->slot, $exception->getMessage());
    }

    private function sendToTelegram(TelegramClient $telegram, ?string $chatId, string $text): void
    {
        if ($chatId === null || $chatId === '') {
            throw new TelegramException('Telegram is not linked.');
        }

        $telegram->send($chatId, $text);
    }
}
