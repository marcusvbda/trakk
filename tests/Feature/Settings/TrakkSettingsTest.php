<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function profileInput(User $user, array $overrides = []): array
{
    return [
        'name' => $user->name,
        'email' => $user->email,
        'timezone' => 'Europe/Dublin',
        'daily_points_target' => 14,
        'telegram_chat_id' => null,
        ...$overrides,
    ];
}

it('offers every timezone on the profile page', function () {
    $this->actingAs($this->user)
        ->get(route('profile.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/profile')
            ->where('timezones', fn ($zones) => collect($zones)->contains('Europe/Dublin') && collect($zones)->contains('America/Sao_Paulo')));
});

it('saves the timezone, the daily target and the telegram chat id', function () {
    $this->actingAs($this->user)
        ->patch(route('profile.update'), profileInput($this->user, [
            'timezone' => 'America/Sao_Paulo',
            'daily_points_target' => 20,
            'telegram_chat_id' => '123456789',
        ]))
        ->assertSessionHasNoErrors();

    expect($this->user->fresh())
        ->timezone->toBe('America/Sao_Paulo')
        ->daily_points_target->toBe(20)
        ->telegram_chat_id->toBe('123456789');
});

it('unlinks telegram when the chat id is cleared', function () {
    $this->user->update(['telegram_chat_id' => '123']);

    $this->actingAs($this->user)->patch(route('profile.update'), profileInput($this->user, ['telegram_chat_id' => '']));

    expect($this->user->fresh()->telegram_chat_id)->toBeNull();
});

it('rejects invalid values', function (array $overrides, string $field) {
    $this->actingAs($this->user)
        ->patch(route('profile.update'), profileInput($this->user, $overrides))
        ->assertSessionHasErrors($field);
})->with([
    'an unknown timezone' => [['timezone' => 'Mars/Olympus'], 'timezone'],
    'a target of zero' => [['daily_points_target' => 0], 'daily_points_target'],
    'a target above fifty' => [['daily_points_target' => 51], 'daily_points_target'],
    'a chat id with letters' => [['telegram_chat_id' => 'abc'], 'telegram_chat_id'],
]);
