<?php

use App\Support\Locales;
use App\Support\Settings;
use Illuminate\Support\Facades\Route;

if (! function_exists('lroute')) {
    /**
     * Localised route URL. The default locale has no prefix; other enabled
     * locales use /{locale}/... with the same route name prefixed "l.".
     */
    function lroute(string $name, mixed $parameters = [], ?string $locale = null, bool $absolute = true): string
    {
        $locale ??= app()->getLocale();
        $parameters = is_array($parameters) ? $parameters : [$parameters];

        if ($locale !== Locales::default() && Locales::isEnabled($locale) && Route::has('l.'.$name)) {
            return route('l.'.$name, array_merge(['locale' => $locale], $parameters), $absolute);
        }

        return route($name, $parameters, $absolute);
    }
}

if (! function_exists('locale_switch_url')) {
    /** URL of the current page in another locale (falls back to that locale's homepage). */
    function locale_switch_url(string $locale): string
    {
        $route = request()->route();
        $name = $route?->getName();
        if ($name && $route->getAction('localized')) {
            $base = str_starts_with($name, 'l.') ? substr($name, 2) : $name;
            $params = collect($route->originalParameters())->except('locale')->all();

            return lroute($base, $params, $locale).(request()->getQueryString() ? '?'.request()->getQueryString() : '');
        }

        return lroute('home', [], $locale);
    }
}

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return Settings::get($key, $default);
    }
}

if (! function_exists('local_time')) {
    /** Format a UTC instant in the display time zone (Africa/Lagos by default). */
    function local_time($value, string $format = 'j M Y, g:i a', ?string $tz = null): ?string
    {
        if (! $value) {
            return null;
        }

        return \Illuminate\Support\Carbon::parse($value)->setTimezone($tz ?? config('app.display_timezone'))->translatedFormat($format);
    }
}

if (! function_exists('localize_url')) {
    /** Prefix CMS-entered site-relative links ("/events") with the active locale. */
    function localize_url(?string $url): ?string
    {
        if (! $url || ! str_starts_with($url, '/') || str_starts_with($url, '//')) {
            return $url;
        }
        $locale = app()->getLocale();
        if ($locale === Locales::default() || ! Locales::isEnabled($locale)) {
            return url($url);
        }

        return url('/'.$locale.($url === '/' ? '' : $url));
    }
}
