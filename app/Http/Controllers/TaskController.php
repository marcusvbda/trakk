<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Goal;
use App\Models\Task;
use App\Services\Tasks\TaskService;
use App\Support\Clock;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class TaskController extends Controller
{
    public function __construct(
        private readonly TaskService $tasks,
        private readonly Clock $clock,
    ) {}

    public function store(StoreTaskRequest $request, Goal $goal): RedirectResponse
    {
        $this->tasks->create($goal, $request->toData());

        Toast::success('task added');

        return back();
    }

    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $this->tasks->update($task, $request->toData(), $this->clock->today($request->user()));

        Toast::success('task saved');

        return back();
    }

    public function destroy(Task $task): RedirectResponse
    {
        Gate::authorize('delete', $task);

        $this->tasks->delete($task);

        Toast::success('task deleted');

        return back();
    }
}
