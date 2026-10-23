<?php

declare(strict_types=1);

namespace App\Data\Goals;

use App\Data\ReminderFormData;

final readonly class GoalReminderData
{
    /**
     * @param  list<string>  $channels
     */
    public function __construct(
        public int $id,
        public string $whenLabel,
        public string $label,
        public string $detailLabel,
        public array $channels,
        public ReminderFormData $form,
    ) {}
}
