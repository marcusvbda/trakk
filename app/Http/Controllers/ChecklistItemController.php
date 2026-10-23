<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreChecklistItemRequest;
use App\Http\Requests\UpdateChecklistItemRequest;
use App\Models\ChecklistItem;
use App\Models\Task;
use App\Services\Tasks\TaskService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class ChecklistItemController extends Controller
{
    public function __construct(private readonly TaskService $tasks) {}

    public function store(StoreChecklistItemRequest $request, Task $task): RedirectResponse
    {
        $this->tasks->addChecklistItem($task, $request->title());

        Toast::success('checklist item added');

        return back();
    }

    public function update(UpdateChecklistItemRequest $request, ChecklistItem $item): RedirectResponse
    {
        $this->tasks->updateChecklistItem($item, $request->title(), $request->isDone());

        if ($request->title() !== null) {
            Toast::success('checklist item saved');
        }

        return back();
    }

    public function destroy(ChecklistItem $item): RedirectResponse
    {
        Gate::authorize('delete', $item);

        $this->tasks->deleteChecklistItem($item);

        Toast::success('checklist item removed');

        return back();
    }
}
