<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Enums\ActivitySource;
use App\Models\User;
use App\Support\Clock;

final class McpStatusService
{
    private const int LIVE_WINDOW_HOURS = 24;

    public function __construct(private readonly Clock $clock) {}

    public function toolCount(): int
    {
        return (int) config('trakk.mcp_tools');
    }

    /** Whether Claude logged anything through MCP in the last 24 hours. */
    public function isLive(User $user): bool
    {
        return $user->activities()
            ->where('source', ActivitySource::Mcp)
            ->where('occurred_at', '>=', $this->clock->now()->subHours(self::LIVE_WINDOW_HOURS))
            ->exists();
    }
}
