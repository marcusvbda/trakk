<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use App\Exceptions\TelegramException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

final class TelegramClient
{
    private const string API = 'https://api.telegram.org';

    private const int REQUEST_SECONDS = 15;

    private const int RETRIES = 2;

    private const int RETRY_WAIT_MILLISECONDS = 500;

    public function isConfigured(): bool
    {
        return $this->token() !== '';
    }

    public function send(string $chatId, string $text): void
    {
        $this->call('sendMessage', [
            'chat_id' => $chatId,
            'text' => $text,
            'disable_web_page_preview' => true,
        ]);
    }

    /**
     * Messages sent to the bot since `$offset`, waiting up to `$waitSeconds` for one to arrive.
     *
     * @return list<array{updateId: int, chatId: string, text: string}>
     */
    public function messages(?int $offset = null, int $waitSeconds = 0): array
    {
        $result = $this->call('getUpdates', array_filter(
            ['offset' => $offset, 'timeout' => $waitSeconds],
            fn (?int $value): bool => $value !== null,
        ))->json('result');
        $messages = [];

        foreach (is_array($result) ? $result : [] as $update) {
            if (! isset($update['update_id'], $update['message']['chat']['id'], $update['message']['text'])) {
                continue;
            }

            $messages[] = [
                'updateId' => (int) $update['update_id'],
                'chatId' => (string) $update['message']['chat']['id'],
                'text' => (string) $update['message']['text'],
            ];
        }

        return $messages;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function call(string $method, array $payload): Response
    {
        if (! $this->isConfigured()) {
            throw new TelegramException('TELEGRAM_BOT_TOKEN is not set.');
        }

        try {
            $response = Http::retry(self::RETRIES, self::RETRY_WAIT_MILLISECONDS, throw: false)
                ->timeout(self::REQUEST_SECONDS + (int) ($payload['timeout'] ?? 0))
                ->post(self::API.'/bot'.$this->token().'/'.$method, $payload);
        } catch (ConnectionException) {
            throw new TelegramException('Could not reach Telegram.');
        }

        if ($response->failed()) {
            throw new TelegramException((string) $response->json('description', 'Telegram refused the request.'));
        }

        return $response;
    }

    private function token(): string
    {
        return (string) config('services.telegram.bot_token');
    }
}
