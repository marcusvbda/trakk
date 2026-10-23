<?php

declare(strict_types=1);

namespace App\Support;

use Inertia\Inertia;

/**
 * Queues the message the next page shows as a toast (read by `use-flash-toast.ts`).
 */
final class Toast
{
    public static function success(string $message): void
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);
    }

    public static function error(string $message): void
    {
        Inertia::flash('toast', ['type' => 'error', 'message' => $message]);
    }
}
