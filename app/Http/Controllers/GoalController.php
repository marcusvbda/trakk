<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Mocks\GoalDetailMock;
use Inertia\Inertia;
use Inertia\Response;

final class GoalController extends Controller
{
    public function show(string $goal): Response
    {
        return Inertia::render('goals/show', GoalDetailMock::props());
    }
}
