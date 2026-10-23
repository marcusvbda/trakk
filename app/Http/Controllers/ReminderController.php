<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreReminderRequest;
use App\Http\Requests\UpdateReminderRequest;
use App\Models\Reminder;
use App\Services\Reminders\ReminderService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class ReminderController extends Controller
{
    public function __construct(private readonly ReminderService $reminders) {}

    public function store(StoreReminderRequest $request): RedirectResponse
    {
        $this->reminders->create($request->user(), $request->toData());

        Toast::success('reminder added');

        return back();
    }

    public function update(UpdateReminderRequest $request, Reminder $reminder): RedirectResponse
    {
        $this->reminders->update($reminder, $request->toData());

        Toast::success('reminder saved');

        return back();
    }

    public function destroy(Reminder $reminder): RedirectResponse
    {
        Gate::authorize('delete', $reminder);

        $this->reminders->delete($reminder);

        Toast::success('reminder deleted');

        return back();
    }
}
