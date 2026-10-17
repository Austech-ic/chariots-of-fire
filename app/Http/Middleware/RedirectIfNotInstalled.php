<?php

namespace App\Http\Middleware;

use App\Support\Installer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Sends every request to the web installer until installation completes, then locks it. */
class RedirectIfNotInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        $installRoute = $request->is('install', 'install/*');

        if (Installer::isInstalled()) {
            if ($installRoute) {
                abort(404);
            }

            return $next($request);
        }

        if (! $installRoute && ! $request->is('up')) {
            return redirect('/install');
        }

        return $next($request);
    }
}
