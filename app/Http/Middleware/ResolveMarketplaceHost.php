<?php

namespace App\Http\Middleware;

use App\Http\Controllers\marketplace\MarketplaceLandingController;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs before routing. Any request whose Host doesn't match this panel's own
 * host is treated as a possible "for sale" domain and handed straight to the
 * marketplace landing controller, bypassing the admin routes entirely.
 *
 * See docs/04-arquitectura-multidominio.md.
 */
class ResolveMarketplaceHost
{
    public function handle(Request $request, Closure $next): Response
    {
        $adminHost = parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost';

        if ($request->getHost() === $adminHost) {
            return $next($request);
        }

        $controller = app(MarketplaceLandingController::class);

        if ($request->isMethod('post') && $request->is('offers')) {
            return response($controller->storeOffer($request));
        }

        if ($request->isMethod('get') && $request->is('metrics')) {
            return response($controller->metrics($request));
        }

        if ($request->isMethod('get') && $request->is('offers')) {
            return response($controller->offers($request));
        }

        return response($controller->show($request));
    }
}
