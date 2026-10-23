<?php

declare(strict_types=1);

namespace App\Services\Reminders;

use App\Enums\ReminderKind;
use App\Models\Reminder;
use App\Services\Dashboard\UserSnapshotBuilder;
use App\Services\Reminders\Messages\CustomMessage;
use App\Services\Reminders\Messages\DailyBriefMessage;
use App\Services\Reminders\Messages\DeadlineAlertMessage;
use App\Services\Reminders\Messages\DueTomorrowMessage;
use App\Services\Reminders\Messages\EveningCheckinMessage;
use App\Services\Reminders\Messages\OffPaceMessage;
use App\Services\Reminders\Messages\PaceSummaryMessage;
use App\Services\Reminders\Messages\ReminderMessage;
use App\Services\Reminders\Messages\WeeklyReviewMessage;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Container\Container;

final class ReminderMessageBuilder
{
    public function __construct(
        private readonly UserSnapshotBuilder $snapshots,
        private readonly Container $container,
    ) {}

    public function build(Reminder $reminder, CarbonImmutable $today): string
    {
        $reminder->loadMissing('user');

        return $this->message($reminder->kind)->build($reminder, $this->snapshots->for($reminder->user, $today));
    }

    private function message(ReminderKind $kind): ReminderMessage
    {
        return $this->container->make(match ($kind) {
            ReminderKind::DailyBrief => DailyBriefMessage::class,
            ReminderKind::EveningCheckin => EveningCheckinMessage::class,
            ReminderKind::DueTomorrow => DueTomorrowMessage::class,
            ReminderKind::WeeklyReview => WeeklyReviewMessage::class,
            ReminderKind::OffPace => OffPaceMessage::class,
            ReminderKind::PaceSummary => PaceSummaryMessage::class,
            ReminderKind::DeadlineAlert => DeadlineAlertMessage::class,
            ReminderKind::Custom => CustomMessage::class,
        });
    }
}
