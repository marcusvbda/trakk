<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CompletionDayRequest;
use App\Models\Task;
use App\Services\Tasks\CompletionService;
use App\Support\Clock;
use Illuminate\Http\RedirectResponse;

final class CompletionController extends Controller
{
    public function __construct(
        private readonly CompletionService $completions,
        private readonly Clock $clock,
    ) {}

    public function store(CompletionDayRequest $request, Task $task): RedirectResponse
    {
        $this->completions->complete($task, $request->day($this->clock->today($request->user())));

        return back();
    }

    public function destroy(CompletionDayRequest $request, Task $task): RedirectResponse
    {
        $this->completions->undo($task, $request->day($this->clock->today($request->user())));

        return back();
    }
}
