<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ChecklistItem;
use App\Models\User;

final class ChecklistItemPolicy
{
    public function __construct(private readonly TaskPolicy $tasks) {}

    public function update(User $user, ChecklistItem $item): bool
    {
        return $this->tasks->update($user, $item->loadMissing('task')->task);
    }

    public function delete(User $user, ChecklistItem $item): bool
    {
        return $this->update($user, $item);
    }
}
