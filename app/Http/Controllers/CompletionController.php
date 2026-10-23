<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Task;
use App\Services\Tasks\CompletionService;
use App\Support\Clock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class CompletionController extends Controller
{
    public function __construct(
        private readonly CompletionService $completions,
        private readonly Clock $clock,
    ) {}

    public function store(Request $request, Task $task): RedirectResponse
    {
        Gate::authorize('update', $task);

        $this->completions->complete($task, $this->clock->today($request->user()));

        return back();
    }

    public function destroy(Request $request, Task $task): RedirectResponse
    {
        Gate::authorize('update', $task);

        $this->completions->undo($task, $this->clock->today($request->user()));

        return back();
    }
}
