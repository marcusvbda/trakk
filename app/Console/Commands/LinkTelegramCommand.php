<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Exceptions\TelegramException;
use App\Models\User;
use App\Services\Telegram\TelegramClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('telegram:link')]
#[Description('Link the owner to Telegram: send /start to the bot and its chat id is saved')]
final class LinkTelegramCommand extends Command
{
    private const int POLLS = 6;

    private const int POLL_WAIT_SECONDS = 10;

    public function handle(TelegramClient $telegram): int
    {
        $owner = User::query()->where('email', config('trakk.owner.email'))->first();

        if ($owner === null) {
            $this->components->error('No owner account: set OWNER_EMAIL and run php artisan db:seed.');

            return self::FAILURE;
        }

        try {
            $chatId = $this->waitForStart($telegram);

            if ($chatId === null) {
                $this->components->error('No /start received in 60 seconds. Run the command again and message the bot.');

                return self::FAILURE;
            }

            $owner->update(['telegram_chat_id' => $chatId]);
            $telegram->send($chatId, 'trakk · linked. Reminders will arrive here.');
        } catch (TelegramException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Linked to chat {$chatId}.");

        return self::SUCCESS;
    }

    /** Works in a private chat and in a group the bot belongs to. */
    private function waitForStart(TelegramClient $telegram): ?string
    {
        $this->components->info('Send /start to your bot, in a private chat or in a group it belongs to. Waiting up to 60 seconds…');

        $offset = null;

        for ($poll = 0; $poll < self::POLLS; $poll++) {
            $messages = $telegram->messages($offset, self::POLL_WAIT_SECONDS);
            $start = collect($messages)->last(fn (array $message): bool => str_starts_with($message['text'], '/start'));

            if ($messages !== []) {
                $offset = collect($messages)->max('updateId') + 1;
            }

            if ($start !== null) {
                $telegram->messages($offset);

                return $start['chatId'];
            }
        }

        return null;
    }
}
