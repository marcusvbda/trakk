<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ReplanRequest;
use App\Services\Tasks\TaskService;
use App\Support\Clock;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

final class ReplanController extends Controller
{
    public function __construct(
        private readonly TaskService $tasks,
        private readonly Clock $clock,
    ) {}

    public function store(ReplanRequest $request): RedirectResponse
    {
        $tasks = $request->tasks();

        $this->tasks->replan($tasks, $request->toData(), $this->clock->today($request->user()));

        Toast::success($tasks->count().' '.Str::plural('task', $tasks->count()).' replanned');

        return back();
    }
}
