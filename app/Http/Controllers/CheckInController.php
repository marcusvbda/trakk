<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Activity\CheckInService;
use App\Support\Clock;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class CheckInController extends Controller
{
    public function __construct(
        private readonly CheckInService $checkIns,
        private readonly Clock $clock,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $checkIn = $this->checkIns->closeDay($user, $this->clock->today($user));

        Toast::success($checkIn->message);

        return back();
    }
}
