<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ReminderChannel;
use App\Enums\ReminderKind;
use App\Models\Goal;
use App\Models\Reminder;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reminder>
 */
final class ReminderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'goal_id' => null,
            'kind' => ReminderKind::DailyBrief,
            'label' => 'Daily brief',
            'time' => '07:30:00',
            'days_of_week' => [1, 2, 3, 4, 5, 6, 7],
            'fire_on' => null,
            'channels' => [ReminderChannel::Telegram],
            'is_active' => true,
            'last_sent_at' => null,
            'last_error' => null,
        ];
    }

    public function forGoal(Goal $goal, ReminderKind $kind = ReminderKind::PaceSummary): self
    {
        return $this->state(['user_id' => $goal->user_id, 'goal_id' => $goal->id, 'kind' => $kind]);
    }

    public function once(CarbonImmutable $on): self
    {
        return $this->state(['fire_on' => $on, 'days_of_week' => null]);
    }

    /**
     * @param  list<int>  $days
     */
    public function onDays(array $days): self
    {
        return $this->state(['days_of_week' => $days, 'fire_on' => null]);
    }

    public function inactive(): self
    {
        return $this->state(['is_active' => false]);
    }
}
