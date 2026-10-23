<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardService;
use App\Support\Clock;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
        private readonly Clock $clock,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('dashboard', $this->dashboard->for($user, $this->clock->today($user))->toProps());
    }
}
