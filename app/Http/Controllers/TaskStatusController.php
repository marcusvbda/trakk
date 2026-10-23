<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\MoveTaskRequest;
use App\Models\Task;
use App\Services\Tasks\TaskService;
use App\Support\Clock;
use Illuminate\Http\RedirectResponse;

final class TaskStatusController extends Controller
{
    public function __construct(
        private readonly TaskService $tasks,
        private readonly Clock $clock,
    ) {}

    public function update(MoveTaskRequest $request, Task $task): RedirectResponse
    {
        $this->tasks->moveTo($task, $request->toStatus(), $this->clock->today($request->user()));

        return back();
    }
}
