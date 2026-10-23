<?php

declare(strict_types=1);

use App\Enums\ActivitySource;
use App\Models\Activity;
use App\Models\User;
use App\Services\Dashboard\McpStatusService;

beforeEach(function () {
    $this->travelTo(day('2026-10-23')->setTime(12, 0));
    $this->user = User::factory()->create();
});

describe('isLive', function () {
    it('is live when Claude logged something through MCP in the last 24 hours', function () {
        Activity::factory()->for($this->user)->create(['source' => ActivitySource::Mcp, 'occurred_at' => now()->subHours(23)]);

        expect(app(McpStatusService::class)->isLive($this->user))->toBeTrue();
    });

    it('is idle when the last MCP call is older than 24 hours', function () {
        Activity::factory()->for($this->user)->create(['source' => ActivitySource::Mcp, 'occurred_at' => now()->subHours(25)]);

        expect(app(McpStatusService::class)->isLive($this->user))->toBeFalse();
    });

    it('ignores activity that did not come through MCP and other users', function () {
        Activity::factory()->for($this->user)->create(['source' => ActivitySource::Web, 'occurred_at' => now()->subHour()]);
        Activity::factory()->create(['source' => ActivitySource::Mcp, 'occurred_at' => now()->subHour()]);

        expect(app(McpStatusService::class)->isLive($this->user))->toBeFalse();
    });
});
