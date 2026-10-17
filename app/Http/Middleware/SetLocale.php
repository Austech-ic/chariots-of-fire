<?php

namespace App\Http\Middleware;

use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();
        $locale = $route?->parameter('locale');

        if ($locale !== null) {
            // Prefixed URL: must be an enabled, non-default locale.
            if (! in_array($locale, Locales::prefixed(), true)) {
                abort(404);
            }
            $route->forgetParameter('locale');
        } elseif ($route?->getAction('localized')) {
            $locale = Locales::default();
        } else {
            // Member portal and other unprefixed areas follow the visitor's choice.
            $locale = $request->user()?->locale ?? $request->session()->get('locale', Locales::default());
            if (! Locales::isEnabled($locale)) {
                $locale = Locales::default();
            }
        }

        app()->setLocale($locale);
        if ($request->hasSession()) {
            $request->session()->put('locale', $locale);
        }

        $response = $next($request);
        $response->headers->set('Content-Language', $locale);

        return $response;
    }
}
