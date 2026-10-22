<?php

use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\RedirectIfNotInstalled;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

// First run on a fresh upload (e.g. shared hosting without SSH): create .env
// from the example with a random application key so the web installer can boot.
$basePath = dirname(__DIR__);
if (! file_exists($basePath.'/.env') && file_exists($basePath.'/.env.example') && is_writable($basePath)) {
    $env = file_get_contents($basePath.'/.env.example');
    $env = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY=base64:'.base64_encode(random_bytes(32)), $env);
    file_put_contents($basePath.'/.env', $env);
    @chmod($basePath.'/.env', 0600);
}

return Application::configure(basePath: $basePath)
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(prepend: [RedirectIfNotInstalled::class], append: [SetLocale::class, SecurityHeaders::class]);
        $middleware->alias(['active' => EnsureAccountActive::class]);
        $middleware->preventRequestForgery(except: ['webhooks/*']);
        // The theme preference is set from JavaScript, so it is stored unencrypted (it holds only 'light' or 'dark').
        $middleware->encryptCookies(except: [\App\Support\Theme::COOKIE]);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('member.dashboard'));

        // Trusted proxies come from config/trustedproxy.php (TRUSTED_PROXIES).
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
