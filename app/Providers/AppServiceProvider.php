<?php

namespace App\Providers;

use App\Models\Prompt;
use App\Models\PromptCollection;
use App\Policies\PromptCollectionPolicy;
use App\Policies\PromptPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
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
        if (app()->isProduction() && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Gate::policy(Prompt::class, PromptPolicy::class);
        Gate::policy(PromptCollection::class, PromptCollectionPolicy::class);

        RateLimiter::for('api-login', fn (Request $request) => Limit::perMinute(5)->by(
            Str::lower((string) $request->input('email')).'|'.$request->ip()
        ));

        RateLimiter::for('prompt-analysis-api', fn (Request $request) => Limit::perMinute(
            max(1, (int) config('prompt-analysis.burst_per_minute'))
        )->by('analysis-api:'.($request->user()?->id ?? $request->ip())));
    }
}
