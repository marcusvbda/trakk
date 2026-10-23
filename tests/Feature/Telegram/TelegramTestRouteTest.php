<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['services.telegram.bot_token' => 'test-token']);
});

it('sends a test message to the saved chat and says so', function () {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => []])]);
    $user = User::factory()->create(['telegram_chat_id' => '-5384957969']);

    $this->actingAs($user)
        ->post(route('telegram.test'))
        ->assertRedirect()
        ->assertSessionHas('inertia.flash_data.toast', ['type' => 'success', 'message' => 'test message sent']);

    Http::assertSent(fn (Request $request): bool => $request['chat_id'] === '-5384957969');
});

it('says Telegram is not linked when there is no chat id', function () {
    Http::fake();
    $user = User::factory()->create(['telegram_chat_id' => null]);

    $this->actingAs($user)
        ->post(route('telegram.test'))
        ->assertSessionHas('inertia.flash_data.toast.type', 'error');

    Http::assertNothingSent();
});

it('shows what Telegram answered when it refuses', function () {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Bad Request: chat not found'], 400)]);
    $user = User::factory()->create(['telegram_chat_id' => '1']);

    $this->actingAs($user)
        ->post(route('telegram.test'))
        ->assertSessionHas('inertia.flash_data.toast', ['type' => 'error', 'message' => 'bad request: chat not found']);
});

it('redirects guests to the sign in page', function () {
    $this->post(route('telegram.test'))->assertRedirect(route('login'));
});
