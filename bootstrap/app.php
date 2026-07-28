<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\LocaleMiddleware;
use App\Http\Middleware\ResolveMarketplaceHost;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prepend(ResolveMarketplaceHost::class);
        $middleware->web(LocaleMiddleware::class);
        $middleware->redirectGuestsTo('/auth/login-basic');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
