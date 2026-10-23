<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreGoalRequest;
use App\Http\Requests\UpdateGoalRequest;
use App\Models\Goal;
use App\Services\Goals\GoalDetailService;
use App\Services\Goals\GoalService;
use App\Services\Goals\GoalsListService;
use App\Support\Clock;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class GoalController extends Controller
{
    public function __construct(
        private readonly GoalService $goals,
        private readonly GoalDetailService $detail,
        private readonly GoalsListService $list,
        private readonly Clock $clock,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('goals/index', $this->list->for($request->user())->toProps());
    }

    public function store(StoreGoalRequest $request): RedirectResponse
    {
        $goal = $this->goals->create($request->user(), $request->toData());

        Toast::success('goal created');

        return to_route('goals.show', $goal);
    }

    public function show(Request $request, Goal $goal): Response
    {
        Gate::authorize('view', $goal);

        return Inertia::render('goals/show', $this->detail->for($goal, $this->clock->today($request->user()))->toProps());
    }

    public function update(UpdateGoalRequest $request, Goal $goal): RedirectResponse
    {
        $goal = $this->goals->update($goal, $request->toData());

        Toast::success('goal saved');

        return to_route('goals.show', $goal);
    }

    public function destroy(Goal $goal): RedirectResponse
    {
        Gate::authorize('delete', $goal);

        $this->goals->delete($goal);

        Toast::success('goal deleted');

        return to_route('dashboard');
    }
}
