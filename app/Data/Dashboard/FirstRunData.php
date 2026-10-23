<?php

declare(strict_types=1);

namespace App\Data\Dashboard;

final readonly class FirstRunData
{
    public function __construct(public bool $isTelegramLinked) {}
}
