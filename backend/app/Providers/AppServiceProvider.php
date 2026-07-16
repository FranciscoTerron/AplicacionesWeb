<?php

namespace App\Providers;

use App\Auth\FirestoreUserProvider;
use App\Models\User;
use App\Policies\UserPolicy;
use App\Services\FirestoreService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FirestoreService::class, function ($app) {
            return new FirestoreService;
        });
    }

    public function boot(): void
    {
        // Force HTTPS en producción o Vercel
        if ($this->app->environment('production') || isset($_SERVER['VERCEL'])) {
            URL::forceScheme('https');
        }

        Auth::provider('firestore', function ($app, array $config) {
            return new FirestoreUserProvider;
        });

        // Register UserPolicy for User model (array-based)
        Gate::policy(User::class, UserPolicy::class);

        // Define 'api' rate limiter for throttle middleware
        RateLimiter::for('api', function () {
            return Limit::perMinute(60);
        });

        // Rate limiter estricto para login/register: 5 intentos por minuto por
        // combinación email+IP, para frenar fuerza bruta de credenciales y alta
        // masiva de cuentas. El limiter 'api' global (60/min) sigue aplicando.
        RateLimiter::for('auth', function (Request $request) {
            // En tests el cache 'array' no expira por tiempo y varios casos
            // reusan el mismo email => evitamos 429 espurios sin limitar prod.
            if ($this->app->environment('testing')) {
                return Limit::none();
            }

            $email = strtolower((string) $request->input('email'));

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });
    }
}
