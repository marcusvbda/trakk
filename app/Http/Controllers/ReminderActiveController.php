<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SetReminderActiveRequest;
use App\Models\Reminder;
use App\Services\Reminders\ReminderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class ReminderActiveController extends Controller
{
    public function __construct(private readonly ReminderService $reminders) {}

    public function update(SetReminderActiveRequest $request, Reminder $reminder): RedirectResponse
    {
        Gate::authorize('update', $reminder);

        $this->reminders->setActive($reminder, $request->isActive());

        return back();
    }
}
