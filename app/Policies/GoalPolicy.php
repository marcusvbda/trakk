<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Goal;
use App\Models\User;

final class GoalPolicy
{
    public function view(User $user, Goal $goal): bool
    {
        return $goal->user_id === $user->id;
    }

    public function update(User $user, Goal $goal): bool
    {
        return $this->view($user, $goal);
    }

    public function delete(User $user, Goal $goal): bool
    {
        return $this->view($user, $goal);
    }
}
