@php
    $nav = [
        ['label' => __('About'), 'children' => [
            ['label' => __('Vision & mission'), 'url' => lroute('about')],
            ['label' => __('Ministry mandates'), 'url' => lroute('about').'#mandates'],
            ['label' => __('Our founder'), 'url' => lroute('founder')],
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
<header data-sticky-header class="group/header fixed inset-x-0 top-0 z-50 transition-colors duration-300 {{ $solid ? 'is-scrolled' : '' }} [&.is-scrolled]:bg-ink/95 [&.is-scrolled]:shadow-lg [&.is-scrolled]:backdrop-blur">
    <div class="container-site flex h-18 items-center justify-between gap-4 py-3 lg:h-20">
        <a href="{{ lroute('home') }}" class="flex items-center gap-3 text-white" aria-label="{{ setting('site_name') }} — {{ __('Home') }}">
            <span class="flex size-10 items-center justify-center rounded-full bg-gold text-ink"><x-glyph name="flame" class="size-6" /></span>
            <span class="font-display leading-none">
                <span class="block text-base font-extrabold tracking-wide">FLAMING CHARIOT</span>
                <span class="block text-[0.65rem] font-semibold tracking-[0.35em] text-gold">OUTREACH</span>
            </span>
        </a>

        <nav class="hidden items-center gap-1 lg:flex" aria-label="{{ __('Main navigation') }}">
            @foreach ($nav as $item)
                @if (isset($item['children']))
                    <details data-dropdown class="relative">
                        <summary class="flex cursor-pointer list-none items-center gap-1 rounded-full px-3 py-2 text-sm font-medium text-white/90 hover:text-white [&::-webkit-details-marker]:hidden">
                            {{ $item['label'] }} <x-glyph name="chevron-down" class="size-4" />
                        </summary>
                        <div class="absolute left-0 mt-2 w-56 rounded-xl bg-white p-2 shadow-xl ring-1 ring-ink/5">
                            @foreach ($item['children'] as $child)
                                <a href="{{ $child['url'] }}" class="block rounded-lg px-3 py-2 text-sm text-ink hover:bg-ivory">{{ $child['label'] }}</a>
                            @endforeach
                        </div>
                    </details>
                @else
                    <a href="{{ $item['url'] }}" class="rounded-full px-3 py-2 text-sm font-medium text-white/90 hover:text-white">{{ $item['label'] }}</a>
                @endif
            @endforeach
        </nav>

        <div class="flex items-center gap-2">
            @if ($liveNow)
                <a href="{{ lroute('live') }}" class="badge-live hidden sm:inline-flex"><span class="size-2 animate-pulse rounded-full bg-white"></span> {{ __('Watch Live') }}</a>
            @endif

            @if (count($locales) > 1)
                <details data-dropdown class="relative hidden sm:block">
                    <summary class="flex cursor-pointer list-none items-center gap-1 rounded-full px-2 py-2 text-sm font-medium uppercase text-white/90 [&::-webkit-details-marker]:hidden" aria-label="{{ __('Language') }}: {{ $locales[app()->getLocale()]['native_name'] ?? app()->getLocale() }}">
                        <x-glyph name="globe" class="size-5" /> {{ app()->getLocale() }}
                    </summary>
                    <div class="absolute right-0 mt-2 w-44 rounded-xl bg-white p-2 shadow-xl ring-1 ring-ink/5">
                        @foreach ($locales as $code => $lang)
                            <a href="{{ locale_switch_url($code) }}" hreflang="{{ $code }}" lang="{{ $code }}" class="block rounded-lg px-3 py-2 text-sm text-ink hover:bg-ivory {{ $code === app()->getLocale() ? 'font-bold' : '' }}" @if ($code === app()->getLocale()) aria-current="true" @endif>{{ $lang['native_name'] }}</a>
                        @endforeach
                    </div>
                </details>
            @endif

            <a href="{{ auth()->check() ? route('member.dashboard') : route('login') }}" class="hidden rounded-full p-2 text-white/90 hover:text-white sm:inline-flex" aria-label="{{ auth()->check() ? __('Member portal') : __('Member sign in') }}">
                <x-glyph name="user" class="size-5" />
            </a>
            <a href="{{ lroute('give') }}" class="btn-primary btn-sm hidden sm:inline-flex">{{ __('Give') }}</a>

            <button type="button" data-nav-toggle aria-expanded="false" aria-controls="mobile-nav" class="rounded-full p-2 text-white lg:hidden">
                <span class="sr-only">{{ __('Menu') }}</span><x-glyph name="menu" class="size-6" />
            </button>
        </div>
    </div>

    <div id="mobile-nav" data-nav-panel hidden class="fixed inset-0 top-[4.5rem] z-40 overflow-y-auto bg-ink px-4 pb-10 pt-4 lg:hidden">
        <nav aria-label="{{ __('Mobile navigation') }}" class="space-y-6">
            @if ($liveNow)
                <a href="{{ lroute('live') }}" class="badge-live"><span class="size-2 animate-pulse rounded-full bg-white"></span> {{ __('Watch Live') }}</a>
            @endif
            @foreach ($nav as $item)
                <div>
                    @if (isset($item['children']))
                        <p class="eyebrow-light">{{ $item['label'] }}</p>
                        <ul class="mt-2 space-y-1">
                            @foreach ($item['children'] as $child)
                                <li><a href="{{ $child['url'] }}" class="block py-2 text-lg font-semibold text-white">{{ $child['label'] }}</a></li>
                            @endforeach
                        </ul>
                    @else
                        <a href="{{ $item['url'] }}" class="block py-1 text-lg font-semibold text-white">{{ $item['label'] }}</a>
                    @endif
                </div>
            @endforeach
            <div class="flex flex-wrap gap-3 border-t border-white/10 pt-6">
                <a href="{{ lroute('give') }}" class="btn-primary">{{ __('Give') }}</a>
                <a href="{{ auth()->check() ? route('member.dashboard') : route('login') }}" class="btn-outline-light">{{ auth()->check() ? __('Member portal') : __('Member sign in') }}</a>
            </div>
            @if (count($locales) > 1)
                <div class="flex flex-wrap gap-2">
                    @foreach ($locales as $code => $lang)
                        <a href="{{ locale_switch_url($code) }}" lang="{{ $code }}" class="rounded-full border border-white/20 px-3 py-1.5 text-sm text-white {{ $code === app()->getLocale() ? 'bg-white/10' : '' }}">{{ $lang['native_name'] }}</a>
                    @endforeach
                </div>
            @endif
        </nav>
    </div>
</header>
