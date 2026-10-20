<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\HandleRedirects;
use App\Http\Middleware\NoIndex;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\ThrottlePasswordResets;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware(['web', 'auth', 'active', 'noindex'])
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));

            Route::middleware('web')->group(base_path('routes/pages.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);
        $middleware->prepend(HandleRedirects::class);

        $middleware->web(append: [
            ThrottlePasswordResets::class,
        ]);

        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'noindex' => NoIndex::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('content:publish-scheduled')->everyMinute()->withoutOverlapping();
        $schedule->command('careers:close-expired')->everyFifteenMinutes()->withoutOverlapping();
        $schedule->command('applications:purge-expired')->dailyAt('02:30')->withoutOverlapping();
        $schedule->command('auth:clear-resets')->daily();

        /*
         * Shared hosting (e.g. Hostinger) cannot run a permanent queue worker,
         * so the scheduler drains the database queue once a minute instead.
         * Disable with QUEUE_WORK_VIA_SCHEDULER=false when running a supervisor.
         */
        if (config('dxcovr.queue_via_scheduler')) {
            $schedule->command('queue:work --stop-when-empty --tries=3 --max-time=50')
                ->everyMinute()
                ->withoutOverlapping();
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->dontFlash(['current_password', 'password', 'password_confirmation', 'cv']);
    })->create();
