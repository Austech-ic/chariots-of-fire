@props(['title' => null, 'description' => null, 'image' => null, 'overlayHeader' => false, 'canonical' => null, 'noindex' => false])
@php
    $siteName = setting('site_name');
    $fullTitle = $title ? $title.' · '.$siteName : $siteName.' — '.setting('tagline');
    $metaDescription = \Illuminate\Support\Str::limit(strip_tags((string) ($description ?? setting('tagline'))), 160);
    $locales = \App\Support\Locales::all();
    $darkMode = \App\Support\Theme::isDark();
    $ogImage = $image instanceof \App\Models\MediaAsset && $image->isPublic() ? $image->url('lg') : null;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $locales[app()->getLocale()]['direction'] ?? 'ltr' }}" @class(['dark' => $darkMode]) data-theme="{{ $darkMode ? 'dark' : 'light' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    @if ($noindex)<meta name="robots" content="noindex, nofollow">@endif
    <link rel="canonical" href="{{ $canonical ?? url()->current() }}">
    @if (! $noindex && request()->route()?->getAction('localized'))
        @foreach ($locales as $code => $lang)
            <link rel="alternate" hreflang="{{ $code }}" href="{{ locale_switch_url($code) }}">
        @endforeach
        <link rel="alternate" hreflang="x-default" href="{{ locale_switch_url(\App\Support\Locales::default()) }}">
    @endif
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $title ?? $siteName }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="website">
    @if ($ogImage)<meta property="og:image" content="{{ $ogImage }}"><meta name="twitter:card" content="summary_large_image">@endif
    <meta name="color-scheme" content="{{ $darkMode ? 'dark' : 'light' }}">
    <meta name="theme-color" content="{{ $darkMode ? '#101116' : '#f7f4ee' }}">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if (config('fco.analytics_script_url'))
        <script defer nonce="{{ Vite::cspNonce() }}" src="{{ config('fco.analytics_script_url') }}" data-domain="{{ config('fco.analytics_domain') }}"></script>
    @endif
    @stack('head')
</head>
<body class="min-h-screen">
    <a href="#main" class="sr-only z-[100] rounded bg-gold px-4 py-2 font-semibold text-ink focus:not-sr-only focus:fixed focus:left-4 focus:top-4">{{ __('Skip to content') }}</a>

    @include('layouts.partials.header', ['overlay' => $overlayHeader, 'darkMode' => $darkMode])

    <main id="main" tabindex="-1">
        {{ $slot }}
    </main>

    @include('layouts.partials.footer')
</body>
</html>
