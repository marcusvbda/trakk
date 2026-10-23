<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('reminders:dispatch')->everyMinute()->withoutOverlapping();

// Hourly and idempotent instead of "daily at 00:05": the owner's timezone lives in the database, and the scheduler
// runs in UTC, so each scan logs a warning in the first hour after the owner's midnight and never twice.
Schedule::command('tasks:scan-overdue')->hourly()->withoutOverlapping();
Schedule::command('goals:scan-deadlines')->hourly()->withoutOverlapping();
