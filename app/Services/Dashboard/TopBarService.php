<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Data\Dashboard\TopBarData;
use App\Models\User;
use App\Support\Labels;
use Carbon\CarbonImmutable;

final class TopBarService
{
    public function __construct(
        private readonly Labels $labels,
        private readonly McpStatusService $mcp,
    ) {}

    public function for(User $user, CarbonImmutable $today): TopBarData
    {
        return new TopBarData(
            $this->labels->topBarDate($today),
            (int) round($today->dayOfYear / $today->daysInYear * 100),
            $this->mcp->toolCount(),
            $this->mcp->isLive($user),
        );
    }
}
