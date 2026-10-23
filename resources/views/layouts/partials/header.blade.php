@php
    $nav = [
        ['label' => __('About'), 'children' => [
            ['label' => __('Vision & mission'), 'url' => lroute('about')],
            ['label' => __('Ministry mandates'), 'url' => lroute('about').'#mandates'],
            ['label' => __('Our founder'), 'url' => lroute('founder')],
            // Extra pages chosen in Site settings → Menu.
            ...($menuPages ?? collect())->map(fn ($p) => ['label' => $p->tr('title'), 'url' => lroute('pages.show', $p)])->all(),
        ]],
        ['label' => __('Watch & Listen'), 'children' => [
            ['label' => __('Live streaming'), 'url' => lroute('live')],
            ['label' => __('Sermons & series'), 'url' => lroute('sermons.index')],
        ]],
        ['label' => __('Programmes'), 'url' => lroute('events.index')],
        ['label' => __('Ministry Life'), 'url' => lroute('gallery.index')],
        ['label' => __('Connect'), 'children' => [
            ['label' => __('Counselling'), 'url' => lroute('counselling.index')],
            ['label' => __('Contact us'), 'url' => lroute('contact')],
            ['label' => __('Newsletter'), 'url' => lroute('newsletter.show')],
        ]],
    ];
    $locales = \App\Support\Locales::all();
@endphp
<header data-sticky-header @if ($overlay) data-overlay @endif class="site-header fixed inset-x-0 top-0 z-50 transition-[background-color,box-shadow] duration-300">
    @if (setting('announcement_text'))
        <div class="bg-gold px-4 py-2 text-center text-sm font-semibold text-ink">
            @if (setting('announcement_url'))
                <a href="{{ localize_url(setting('announcement_url')) }}" class="underline-offset-2 hover:underline">{{ setting('announcement_text') }} <span aria-hidden="true">→</span></a>
            @else
                {{ setting('announcement_text') }}
            @endif
        </div>
    @endif
    <div class="container-site flex h-18 items-center justify-between gap-4 py-3 lg:h-20">
        <a href="{{ lroute('home') }}" class="flex min-w-0 items-center text-hdr" aria-label="{{ setting('site_name') }} — {{ __('Home') }}">
            <x-site-logo context="header" />
        </a>

        <nav class="hidden items-center gap-1 lg:flex" aria-label="{{ __('Main navigation') }}">
            @foreach ($nav as $item)
                @if (isset($item['children']))
                    <details data-dropdown class="relative">
                        <summary class="flex cursor-pointer list-none items-center gap-1 rounded-full px-3 py-2 text-sm font-medium text-hdr-muted hover:text-hdr [&::-webkit-details-marker]:hidden">
                            {{ $item['label'] }} <x-glyph name="chevron-down" class="size-4" />
                        </summary>
                        <div class="absolute left-0 mt-2 w-56 rounded-xl bg-surface p-2 shadow-xl ring-1 ring-line">
                            @foreach ($item['children'] as $child)
                                <a href="{{ $child['url'] }}" class="block rounded-lg px-3 py-2 text-sm text-fg hover:bg-canvas">{{ $child['label'] }}</a>
                            @endforeach
                        </div>
                    </details>
                @else
                    <a href="{{ $item['url'] }}" class="rounded-full px-3 py-2 text-sm font-medium text-hdr-muted hover:text-hdr">{{ $item['label'] }}</a>
                @endif
            @endforeach
        </nav>

        <div class="flex items-center gap-2">
            @if ($liveNow)
                <a href="{{ lroute('live') }}" class="badge-live hidden sm:inline-flex"><span class="size-2 animate-pulse rounded-full bg-white"></span> {{ __('Watch Live') }}</a>
            @endif

            @if (count($locales) > 1)
                <details data-dropdown class="relative hidden sm:block">
                    <summary class="flex cursor-pointer list-none items-center gap-1 rounded-full px-2 py-2 text-sm font-medium uppercase text-hdr-muted hover:text-hdr [&::-webkit-details-marker]:hidden" aria-label="{{ __('Language') }}: {{ $locales[app()->getLocale()]['native_name'] ?? app()->getLocale() }}">
                        <x-glyph name="globe" class="size-5" /> {{ app()->getLocale() }}
                    </summary>
                    <div class="absolute right-0 mt-2 w-44 rounded-xl bg-surface p-2 shadow-xl ring-1 ring-line">
                        @foreach ($locales as $code => $lang)
                            <a href="{{ locale_switch_url($code) }}" hreflang="{{ $code }}" lang="{{ $code }}" class="block rounded-lg px-3 py-2 text-sm text-fg hover:bg-canvas {{ $code === app()->getLocale() ? 'font-bold' : '' }}" @if ($code === app()->getLocale()) aria-current="true" @endif>{{ $lang['native_name'] }}</a>
                        @endforeach
                    </div>
                </details>
            @endif

            <a href="{{ route('theme.set', $darkMode ? 'light' : 'dark') }}" data-theme-toggle
               data-label-light="{{ __('Switch to dark mode') }}" data-label-dark="{{ __('Switch to light mode') }}"
               aria-label="{{ $darkMode ? __('Switch to light mode') : __('Switch to dark mode') }}" title="{{ $darkMode ? __('Switch to light mode') : __('Switch to dark mode') }}"
               class="rounded-full p-2 text-hdr-muted transition hover:text-hdr">
                <x-glyph name="moon" class="size-5 dark:hidden" />
                <x-glyph name="sun" class="hidden size-5 dark:block" />
            </a>

            <a href="{{ auth()->check() ? route('member.dashboard') : route('login') }}" class="hidden rounded-full p-2 text-hdr-muted hover:text-hdr sm:inline-flex" aria-label="{{ auth()->check() ? __('Member portal') : __('Member sign in') }}">
                <x-glyph name="user" class="size-5" />
            </a>
            <a href="{{ lroute('give') }}" class="btn-primary btn-sm hidden sm:inline-flex">{{ __('Give') }}</a>

            <button type="button" data-nav-toggle aria-expanded="false" aria-controls="mobile-nav" class="rounded-full p-2 text-hdr lg:hidden">
                <span class="sr-only">{{ __('Menu') }}</span><x-glyph name="menu" class="size-6" />
            </button>
        </div>
    </div>

    <div id="mobile-nav" data-nav-panel hidden class="fixed inset-x-0 bottom-0 top-[4.5rem] z-40 overflow-y-auto bg-canvas px-4 pb-10 pt-4 lg:hidden">
        <nav aria-label="{{ __('Mobile navigation') }}" class="space-y-6">
            @if ($liveNow)
                <a href="{{ lroute('live') }}" class="badge-live"><span class="size-2 animate-pulse rounded-full bg-white"></span> {{ __('Watch Live') }}</a>
            @endif
            @foreach ($nav as $item)
                <div>
                    @if (isset($item['children']))
                        <p class="eyebrow">{{ $item['label'] }}</p>
                        <ul class="mt-2 space-y-1">
                            @foreach ($item['children'] as $child)
                                <li><a href="{{ $child['url'] }}" class="block py-2 text-lg font-semibold text-fg">{{ $child['label'] }}</a></li>
                            @endforeach
                        </ul>
                    @else
                        <a href="{{ $item['url'] }}" class="block py-1 text-lg font-semibold text-fg">{{ $item['label'] }}</a>
                    @endif
                </div>
            @endforeach
            <div class="flex flex-wrap gap-3 border-t border-line pt-6">
                <a href="{{ lroute('give') }}" class="btn-primary">{{ __('Give') }}</a>
                <a href="{{ auth()->check() ? route('member.dashboard') : route('login') }}" class="btn-outline">{{ auth()->check() ? __('Member portal') : __('Member sign in') }}</a>
            </div>
            @if (count($locales) > 1)
                <div class="flex flex-wrap gap-2">
                    @foreach ($locales as $code => $lang)
                        <a href="{{ locale_switch_url($code) }}" lang="{{ $code }}" class="rounded-full border border-line px-3 py-1.5 text-sm text-fg {{ $code === app()->getLocale() ? 'bg-fg/10' : '' }}">{{ $lang['native_name'] }}</a>
                    @endforeach
                </div>
            @endif
        </nav>
    </div>
</header>
