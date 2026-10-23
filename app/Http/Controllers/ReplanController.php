<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ReplanRequest;
use App\Services\Tasks\TaskService;
use App\Support\Clock;
use Illuminate\Http\RedirectResponse;

final class ReplanController extends Controller
{
    public function __construct(
        private readonly TaskService $tasks,
        private readonly Clock $clock,
    ) {}

    public function store(ReplanRequest $request): RedirectResponse
    {
        $this->tasks->replan($request->tasks(), $request->toData(), $this->clock->today($request->user()));

        return back();
    }
}
