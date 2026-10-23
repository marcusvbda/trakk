<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ActivitySource;
use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
final class ActivityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'goal_id' => null,
            'task_id' => null,
            'type' => ActivityType::TaskCompleted,
            'message' => fake()->sentence(3),
            'points' => null,
            'source' => ActivitySource::Web,
            'meta' => null,
            'occurred_at' => CarbonImmutable::now(),
        ];
    }
}
