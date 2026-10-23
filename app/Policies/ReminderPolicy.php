<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Reminder;
use App\Models\User;

final class ReminderPolicy
{
    public function update(User $user, Reminder $reminder): bool
    {
        return $reminder->user_id === $user->id;
    }

    public function delete(User $user, Reminder $reminder): bool
    {
        return $this->update($user, $reminder);
    }
}
