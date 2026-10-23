<?php

declare(strict_types=1);

namespace App\Services\Mcp;

use App\Models\User;
use RuntimeException;

/**
 * The local MCP server has no signed-in user; every call acts for the owner account.
 */
final class OwnerResolver
{
    public function owner(): User
    {
        return User::query()->where('email', config('trakk.owner.email'))->first()
            ?? throw new RuntimeException('No owner account: set OWNER_EMAIL and run php artisan db:seed.');
    }
}
