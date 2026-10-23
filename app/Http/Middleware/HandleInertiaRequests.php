<?php

namespace App\Http\Middleware;

use App\Data\CommandIndexData;
use App\Data\Dashboard\TopBarData;
use App\Data\SheetOptionsData;
use App\Services\Dashboard\CommandIndexService;
use App\Services\Dashboard\TopBarService;
use App\Services\Goals\GoalOptionsService;
use App\Support\Clock;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'version' => config('trakk.version'),
            'auth' => [
                'user' => $request->user(),
            ],
            'topBar' => fn () => $this->topBar($request),
            'sheetOptions' => fn () => $this->sheetOptions($request),
            'commandIndex' => Inertia::optional(fn () => $this->commandIndex($request)),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    private function commandIndex(Request $request): ?CommandIndexData
    {
        $user = $request->user();

        if ($user === null) {
            return null;
        }

        return app(CommandIndexService::class)->for($user, app(Clock::class)->today($user));
    }

    private function sheetOptions(Request $request): ?SheetOptionsData
    {
        $user = $request->user();

        if ($user === null) {
            return null;
        }

        return app(GoalOptionsService::class)->for($user, app(Clock::class)->today($user));
    }

    private function topBar(Request $request): ?TopBarData
    {
        $user = $request->user();

        if ($user === null) {
            return null;
        }

        return app(TopBarService::class)->for($user, app(Clock::class)->today($user));
    }
}
