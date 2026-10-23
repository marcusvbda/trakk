<?php

declare(strict_types=1);

namespace App\Data\Goals;

final readonly class GoalsListData
{
    /**
     * @param  list<GoalListGroupData>  $groups  one per status, empty ones included
     */
    public function __construct(public array $groups) {}

    /**
     * @return array<string, mixed>
     */
    public function toProps(): array
    {
        return get_object_vars($this);
    }
}
