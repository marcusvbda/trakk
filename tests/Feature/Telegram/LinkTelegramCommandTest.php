<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['services.telegram.bot_token' => 'test-token', 'trakk.owner.email' => 'owner@example.com']);
    $this->owner = User::factory()->create(['email' => 'owner@example.com']);
});

it('saves the chat that sent /start and confirms in it', function () {
    Http::fake(['api.telegram.org/*' => Http::sequence()
        ->push(['ok' => true, 'result' => []])
        ->push(['ok' => true, 'result' => [['update_id' => 3, 'message' => ['chat' => ['id' => 987654321], 'text' => '/start']]]])
        ->push(['ok' => true, 'result' => []])
        ->push(['ok' => true, 'result' => ['message_id' => 1]])]);

    $this->artisan('telegram:link')->assertSuccessful();

    expect($this->owner->fresh()->telegram_chat_id)->toBe('987654321');
    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/sendMessage') && $request['chat_id'] === '987654321');
});

it('links a group, whose chat id is negative', function () {
    Http::fake(['api.telegram.org/*' => Http::sequence()
        ->push(['ok' => true, 'result' => [['update_id' => 3, 'message' => ['chat' => ['id' => -5384957969], 'text' => '/start@trakk_bot']]]])
        ->push(['ok' => true, 'result' => []])
        ->push(['ok' => true, 'result' => ['message_id' => 1]])]);

    $this->artisan('telegram:link')->assertSuccessful();

    expect($this->owner->fresh()->telegram_chat_id)->toBe('-5384957969');
});

it('gives up when nobody sends /start', function () {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => []])]);

    $this->artisan('telegram:link')->assertFailed();

    expect($this->owner->fresh()->telegram_chat_id)->toBeNull();
});

it('explains a missing token instead of calling Telegram', function () {
    config(['services.telegram.bot_token' => null]);
    Http::fake();

    $this->artisan('telegram:link')->expectsOutputToContain('TELEGRAM_BOT_TOKEN')->assertFailed();

    Http::assertNothingSent();
});

it('fails when there is no owner account', function () {
    $this->owner->delete();

    $this->artisan('telegram:link')->expectsOutputToContain('No owner account')->assertFailed();
});
