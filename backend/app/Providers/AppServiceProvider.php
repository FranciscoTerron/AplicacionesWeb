<?php

namespace App\Providers;

use App\Auth\FirestoreUserProvider;
use App\Services\FirestoreService;
use Illuminate\Support\Facades\Auth;
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
    }
}
