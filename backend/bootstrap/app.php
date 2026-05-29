<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\Api\AcceptJsonHeaderMiddleware;
use App\Http\Middleware\Api\AuthenticateApiToken;
use App\Http\Middleware\Api\ForceJsonResponseMiddleware;
use App\Http\Middleware\ShareSettingsMiddleware;
use App\Providers\AppServiceProvider;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Symfony\Component\HttpFoundation\Cookie;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Web middleware group (session, CSRF, etc.)
        $middleware->web([
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            ValidateCsrfToken::class,
            ShareSettingsMiddleware::class,
            SubstituteBindings::class,
        ]);

        // CORS y headers apropiados para API JSON
        $middleware->api([
            ConvertEmptyStringsToNull::class,
            ValidatePostSize::class,
            PreventRequestsDuringMaintenance::class,
            HandleCors::class,
            SubstituteBindings::class,
            AcceptJsonHeaderMiddleware::class,
            ForceJsonResponseMiddleware::class,
        ]);

        // Add custom middleware aliases
        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'auth.api' => AuthenticateApiToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Si la cookie de sesión quedó cifrada con una APP_KEY anterior (típico tras
        // rotar la key), Laravel tira DecryptException y devuelve 500. En lugar de
        // eso, descartamos la cookie corrupta y devolvemos al usuario al / con un
        // Set-Cookie de borrado: la próxima request ya genera sesión limpia.
        $exceptions->renderable(function (DecryptException $e, $request) {
            $sessionCookie = config('session.cookie');
            $response = redirect($request->fullUrl());
            foreach (array_keys($request->cookies->all()) as $name) {
                if ($name === $sessionCookie || str_starts_with($name, 'XSRF-')) {
                    $response->headers->setCookie(Cookie::create($name)->withValue(null)->withExpires(0));
                }
            }

            return $response;
        });
    })
    ->withProviders([
        AppServiceProvider::class,
    ])
    ->create();
