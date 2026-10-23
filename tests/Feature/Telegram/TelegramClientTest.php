<?php

declare(strict_types=1);

use App\Exceptions\TelegramException;
use App\Services\Telegram\TelegramClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['services.telegram.bot_token' => 'test-token']);
    $this->client = app(TelegramClient::class);
});

describe('sending', function () {
    it('posts the text to the chat through the bot', function () {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => []])]);

        $this->client->send('-5384957969', 'hello');

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.telegram.org/bottest-token/sendMessage'
            && $request['chat_id'] === '-5384957969'
            && $request['text'] === 'hello');
    });

    it('raises the description Telegram gives when it refuses the message', function () {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Forbidden: bot was blocked by the user'], 403)]);

        expect(fn () => $this->client->send('1', 'hello'))
            ->toThrow(TelegramException::class, 'Forbidden: bot was blocked by the user');
    });

    it('never puts the token in the error', function () {
        Http::fake(['api.telegram.org/*' => Http::response('boom', 500)]);

        try {
            $this->client->send('1', 'hello');
        } catch (TelegramException $exception) {
            expect($exception->getMessage())->not->toContain('test-token');
        }
    });

    it('refuses to call Telegram without a token', function () {
        config(['services.telegram.bot_token' => null]);
        Http::fake();

        expect(fn () => $this->client->send('1', 'hello'))->toThrow(TelegramException::class, 'TELEGRAM_BOT_TOKEN');

        Http::assertNothingSent();
    });
});

describe('reading messages', function () {
    it('lists the text messages sent to the bot with their chat', function () {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => [
            ['update_id' => 7, 'message' => ['chat' => ['id' => -5384957969], 'text' => '/start']],
            ['update_id' => 8, 'edited_message' => ['chat' => ['id' => 1], 'text' => 'ignored']],
            ['update_id' => 9, 'message' => ['chat' => ['id' => 42], 'photo' => []]],
        ]])]);

        expect($this->client->messages(5, 10))->toBe([['updateId' => 7, 'chatId' => '-5384957969', 'text' => '/start']]);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/getUpdates') && $request['offset'] === 5 && $request['timeout'] === 10);
    });
});
