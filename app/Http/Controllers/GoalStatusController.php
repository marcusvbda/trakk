<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SetGoalStatusRequest;
use App\Models\Goal;
use App\Services\Goals\GoalService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;

final class GoalStatusController extends Controller
{
    public function __construct(private readonly GoalService $goals) {}

    public function update(SetGoalStatusRequest $request, Goal $goal): RedirectResponse
    {
        $status = $request->toStatus();

        $this->goals->changeStatus($goal, $status);

        Toast::success('goal set to '.$status->value);

        return back();
    }
}
