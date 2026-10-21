<?php

namespace App\Providers;

use App\Models\User;
use App\Services\SiteSettings;
use App\Services\Spam\CaptchaVerifier;
use App\Services\Spam\NullCaptchaVerifier;
use App\Services\Spam\TurnstileCaptchaVerifier;
use App\View\Composers\SiteLayoutComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SiteSettings::class);

        $this->app->singleton(CaptchaVerifier::class, function (): CaptchaVerifier {
            if (config('dxcovr.spam.captcha.driver') === 'turnstile') {
                return new TurnstileCaptchaVerifier(
                    config('dxcovr.spam.captcha.turnstile.site_key'),
                    config('dxcovr.spam.captcha.turnstile.secret_key'),
                );
            }

            return new NullCaptchaVerifier;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        if ($this->app->isProduction() && str_starts_with((string) config('app.url'), 'https://')) {
            // Links and canonical URLs always use APP_URL, whatever host or sub-folder
            // path (e.g. a shared-hosting rewrite target) the request arrived on.
            URL::forceRootUrl(config('app.url'));
            URL::forceScheme('https');
        }

        Gate::before(fn (User $user) => $user->is_active && $user->isSuperAdmin() ? true : null);

        Password::defaults(function () {
            $rule = Password::min(12)->letters()->mixedCase()->numbers();

            return $this->app->isProduction() ? $rule->uncompromised() : $rule;
        });

        $this->configureRateLimiting();

        Paginator::defaultView('components.pagination');

        View::composer(['layouts.site', 'errors::*', 'errors.*'], SiteLayoutComposer::class);
        View::composer(['site.*', 'components.site.*'], fn ($view) => $view->with('site', $this->app->make(SiteSettings::class)));
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('forms', fn (Request $request) => [
            Limit::perMinute(5)->by('forms-minute:'.$request->ip()),
            Limit::perDay(40)->by('forms-day:'.$request->ip()),
        ]);

        RateLimiter::for('applications', fn (Request $request) => [
            Limit::perMinute(3)->by('applications-minute:'.$request->ip()),
            Limit::perDay(15)->by('applications-day:'.$request->ip()),
        ]);

        RateLimiter::for('search', fn (Request $request) => Limit::perMinute(60)->by('search:'.$request->ip()));

        RateLimiter::for('onboarding', fn (Request $request) => Limit::perMinute(30)->by('onboarding:'.$request->ip()));

        RateLimiter::for('onboarding-uploads', fn (Request $request) => [
            Limit::perMinute(20)->by('onboarding-uploads-minute:'.$request->ip()),
            Limit::perDay(100)->by('onboarding-uploads-day:'.$request->ip()),
        ]);

        RateLimiter::for('invoices', fn (Request $request) => Limit::perMinute(30)->by('invoices:'.$request->ip()));

        RateLimiter::for('admin-uploads', fn (Request $request) => Limit::perMinute(60)->by('uploads:'.($request->user()?->id ?: $request->ip())));
    }
}
