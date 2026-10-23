<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use App\Exceptions\TelegramException;
use App\Models\User;

final class TelegramTester
{
    public function __construct(private readonly TelegramClient $telegram) {}

    /**
     * @throws TelegramException when Telegram is not set up or refuses the message
     */
    public function send(User $user): void
    {
        if ($user->telegram_chat_id === null || $user->telegram_chat_id === '') {
            throw new TelegramException('Telegram is not linked: save a chat id first.');
        }

        $this->telegram->send($user->telegram_chat_id, 'trakk · test message. Reminders will arrive here.');
    }
}
