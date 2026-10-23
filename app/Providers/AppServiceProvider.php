<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Goal;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\DevCommands;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerRouteBindings();
        $this->registerDevProcesses();
    }

    /**
     * Goal URLs carry the slug, which is unique per user only, so it resolves among the signed-in user's goals.
     * Routes still declare `{goal:slug}` so Wayfinder types the parameter as a slug.
     */
    private function registerRouteBindings(): void
    {
        Route::bind('goal', fn (string $slug): Goal => request()->user()?->goals()->where('slug', $slug)->firstOrFail() ?? abort(404));
    }

    private function registerDevProcesses(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        DevCommands::artisan('schedule:work', 'scheduler');
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        Model::shouldBeStrict(! app()->isProduction());
        Model::preventLazyLoading(! app()->isProduction());

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
