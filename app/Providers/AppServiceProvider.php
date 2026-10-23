<?php

namespace App\Providers;

use App\Models\Livestream;
use App\Models\Page;
use App\Models\User;
use App\Services\Messaging\LogDriver;
use App\Services\Messaging\MessagingService;
use App\Services\Messaging\TermiiSmsDriver;
use App\Services\Messaging\TwilioDriver;
use App\Services\Messaging\WhatsAppCloudDriver;
use App\Services\Payments\FlutterwaveGateway;
use App\Services\Payments\PaymentManager;
use App\Services\Payments\PaystackGateway;
use App\Services\Payments\TestGateway;
use App\Services\Streaming\EmbedUrlProvider;
use App\Services\Streaming\StreamManager;
use App\Services\Streaming\VimeoProvider;
use App\Services\Streaming\YouTubeProvider;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(StreamManager::class, fn () => new StreamManager([
            new YouTubeProvider(config('fco.streaming.youtube_api_key')),
            new VimeoProvider,
            new EmbedUrlProvider(config('fco.streaming.embed_hosts', [])),
        ]));

        $this->app->singleton(PaymentManager::class, function () {
            $cfg = config('fco.payments');
            $gateways = [];

            if ($cfg['paystack']['secret_key']) {
                $gateways['paystack'] = new PaystackGateway($cfg['paystack']['secret_key'], $cfg['paystack']['currencies'], fallbackEmail: $cfg['fallback_email']);
            }
            if ($cfg['flutterwave']['secret_key']) {
                $gateways['flutterwave'] = new FlutterwaveGateway($cfg['flutterwave']['secret_key'], $cfg['flutterwave']['webhook_hash'], $cfg['flutterwave']['currencies'], fallbackEmail: $cfg['fallback_email']);
            }
            if ($cfg['test']['enabled'] && ! $this->app->isProduction()) {
                $gateways['test'] = new TestGateway($cfg['test']['webhook_secret']);
            }

            $default = isset($gateways[$cfg['default']]) ? $cfg['default'] : (array_key_first($gateways) ?? $cfg['default']);

            return new PaymentManager($gateways, $default);
        });

        $this->app->singleton(MessagingService::class, function () {
            $cfg = config('fco.messaging');
            $sms = match ($cfg['sms_driver']) {
                'termii' => new TermiiSmsDriver($cfg['termii']['api_key'], $cfg['termii']['sender_id']),
                'twilio' => new TwilioDriver($cfg['twilio']['sid'], $cfg['twilio']['token'], $cfg['twilio']['sms_from']),
                'none' => null,
                default => new LogDriver('sms'),
            };
            $whatsapp = match ($cfg['whatsapp_driver']) {
                'whatsapp_cloud' => new WhatsAppCloudDriver($cfg['whatsapp_cloud']['token'], $cfg['whatsapp_cloud']['phone_number_id'], $cfg['whatsapp_cloud']['templates']),
                'twilio' => new TwilioDriver($cfg['twilio']['sid'], $cfg['twilio']['token'], $cfg['twilio']['whatsapp_from'], whatsapp: true),
                'none' => null,
                default => new LogDriver('whatsapp'),
            };

            return new MessagingService(array_filter(['sms' => $sms, 'whatsapp' => $whatsapp]), $cfg['templates']);
        });
    }

    public function boot(): void
    {
        // Generate https:// links whenever the site address is https (works behind proxies that terminate TLS).
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Password::defaults(fn () => Password::min(10)->letters()->numbers());

        // Every permission slug becomes a gate; super-admins are handled in User::hasPermission().
        foreach (array_keys(\App\Support\Permissions::flat()) as $permission) {
            Gate::define($permission, fn (User $user) => $user->hasPermission($permission));
        }

        RateLimiter::for('forms', fn (Request $request) => [
            Limit::perMinute(5)->by('forms:'.$request->ip()),
            Limit::perHour(30)->by('forms-h:'.$request->ip()),
        ]);
        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(6)->by('auth:'.strtolower((string) $request->input('email')).'|'.$request->ip()));
        RateLimiter::for('booking', fn (Request $request) => Limit::perMinute(10)->by('booking:'.$request->ip()));
        RateLimiter::for('webhooks', fn (Request $request) => Limit::perMinute(120)->by('webhook:'.$request->ip()));

        Event::listen(Login::class, function (Login $event) {
            if ($event->user instanceof User) {
                $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
            }
        });

        Vite::useAggressivePrefetching();

        View::composer('layouts.partials.*', function ($view) {
            // Each part fails independently so a database hiccup never breaks the whole layout.
            $shared = request()->attributes->get('fco.layout') ?? [
                'liveNow' => rescue(fn () => Cache::remember('fco.live-now', 30, fn () => Livestream::current()?->only(['id', 'title'])), null, false),
                'footerPages' => rescue(fn () => Page::query()->published()->where('show_in_footer', true)->with('translations')->get(), collect(), false),
                'menuPages' => rescue(function () {
                    $ids = array_filter(array_map('intval', explode(',', (string) setting('menu_page_ids'))));

                    return $ids ? Page::query()->published()->whereKey($ids)->with('translations')->get()
                        ->sortBy(fn ($p) => array_search($p->id, $ids, true))->values() : collect();
                }, collect(), false),
            ];
            request()->attributes->set('fco.layout', $shared);
            $view->with($shared);
        });
    }
}
