<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Exceptions\TelegramException;
use App\Http\Controllers\Controller;
use App\Services\Telegram\TelegramTester;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class TelegramTestController extends Controller
{
    public function __construct(private readonly TelegramTester $tester) {}

    public function store(Request $request): RedirectResponse
    {
        try {
            $this->tester->send($request->user());
        } catch (TelegramException $exception) {
            Toast::error(mb_strtolower($exception->getMessage()));

            return back();
        }

        Toast::success('test message sent');

        return back();
    }
}
