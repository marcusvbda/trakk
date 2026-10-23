<?php

declare(strict_types=1);

use App\Enums\ActivityType;
use App\Enums\ReminderChannel;
use App\Exceptions\TelegramException;
use App\Jobs\SendReminder;
use App\Mail\ReminderMail;
use App\Models\Reminder;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    config(['services.telegram.bot_token' => 'test-token']);
    // Friday 23 oct 2026, 06:30 UTC = 07:30 in Dublin.
    $this->travelTo(CarbonImmutable::parse('2026-10-23 06:30:10', 'UTC'));
    $this->user = User::factory()->create(['timezone' => 'Europe/Dublin', 'telegram_chat_id' => '-5384957969']);
});

function dueReminder(User $user, array $attributes = []): Reminder
{
    return Reminder::factory()->for($user)->onDays([5])->create([
        'kind' => 'custom',
        'label' => 'Stretch now',
        'time' => '07:30:00',
        'channels' => [ReminderChannel::Telegram],
        ...$attributes,
    ]);
}

describe('reminders:dispatch', function () {
    it('queues one job per channel of every reminder that is due', function () {
        Queue::fake();
        dueReminder($this->user, ['channels' => [ReminderChannel::Telegram, ReminderChannel::Email]]);
        dueReminder($this->user, ['time' => '09:00:00']);

        $this->artisan('reminders:dispatch')->assertSuccessful();

        Queue::assertPushed(SendReminder::class, 2);
        Queue::assertPushed(SendReminder::class, fn (SendReminder $job): bool => $job->channel === ReminderChannel::Email);
    });

    it('queues nothing when no reminder is due', function () {
        Queue::fake();
        dueReminder($this->user, ['time' => '21:00:00']);

        $this->artisan('reminders:dispatch')->assertSuccessful();

        Queue::assertNothingPushed();
    });
});

describe('sending through Telegram', function () {
    it('delivers the message once and records it', function () {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => []])]);
        $reminder = dueReminder($this->user, ['last_error' => 'earlier failure']);

        $this->artisan('reminders:dispatch');
        $this->artisan('reminders:dispatch');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request['chat_id'] === '-5384957969' && $request['text'] === 'Stretch now');
        expect($reminder->fresh())->last_error->toBeNull()->last_sent_at->not->toBeNull()
            ->and($this->user->activities()->where('type', ActivityType::ReminderSent)->sole()->message)->toBe('stretch now sent · telegram');
    });

    it('writes the brief from the owner\'s real data', function () {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => []])]);
        $this->user->update(['daily_points_target' => 10]);
        dueReminder($this->user, ['kind' => 'daily_brief', 'label' => 'Daily brief']);

        $this->artisan('reminders:dispatch');

        Http::assertSent(fn (Request $request): bool => str_starts_with($request['text'], "daily brief · fri, 23 oct\ntarget 10 pts"));
    });

    it('logs the failure and keeps the error when Telegram refuses', function () {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Forbidden: bot was kicked'], 403)]);
        $reminder = dueReminder($this->user);
        $job = new SendReminder($reminder, ReminderChannel::Telegram, $reminder->fresh()->last_sent_at ?? CarbonImmutable::parse('2026-10-23 07:30', 'Europe/Dublin'));

        expect(fn () => app()->call($job->handle(...)))->toThrow(TelegramException::class);
        $job->failed(new TelegramException('Forbidden: bot was kicked'));

        $failure = $this->user->activities()->where('type', ActivityType::ReminderFailed)->sole();
        expect($reminder->fresh()->last_error)->toBe('Forbidden: bot was kicked')
            ->and($failure->message)->toBe('stretch now failed · telegram')
            ->and($failure->meta['error'])->toBe('Forbidden: bot was kicked');
    });

    it('does not dispatch the same slot again after it failed', function () {
        Queue::fake();
        $reminder = dueReminder($this->user);
        $slot = CarbonImmutable::parse('2026-10-23 07:30', 'Europe/Dublin');
        (new SendReminder($reminder, ReminderChannel::Telegram, $slot))->failed(new RuntimeException('down'));

        $this->artisan('reminders:dispatch');

        Queue::assertNothingPushed();
    });

    it('fails clearly when Telegram is not linked', function () {
        Http::fake();
        $this->user->update(['telegram_chat_id' => null]);
        $reminder = dueReminder($this->user);
        $job = new SendReminder($reminder, ReminderChannel::Telegram, CarbonImmutable::parse('2026-10-23 07:30', 'Europe/Dublin'));

        expect(fn () => app()->call($job->handle(...)))->toThrow(TelegramException::class, 'not linked');

        Http::assertNothingSent();
    });
});

describe('sending by email', function () {
    it('mails the owner the same text', function () {
        Mail::fake();
        dueReminder($this->user, ['channels' => [ReminderChannel::Email]]);

        $this->artisan('reminders:dispatch');

        Mail::assertSent(ReminderMail::class, fn (ReminderMail $mail): bool => $mail->hasTo($this->user->email)
            && $mail->hasSubject('trakk · stretch now')
            && str_contains($mail->render(), 'Stretch now'));
    });

    it('records the email as sent', function () {
        Mail::fake();
        $reminder = dueReminder($this->user, ['channels' => [ReminderChannel::Email]]);

        $this->artisan('reminders:dispatch');

        expect($reminder->fresh()->last_sent_at)->not->toBeNull()
            ->and($this->user->activities()->where('type', ActivityType::ReminderSent)->sole()->message)->toBe('stretch now sent · email');
    });
});
