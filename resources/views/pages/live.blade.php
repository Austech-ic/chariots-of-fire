<x-layouts.app :title="__('Live streaming')" :description="__('Watch live broadcasts and recorded messages.')">
    <section class="bg-band pb-16 pt-32 text-band-fg sm:pt-36">
        <div class="container-site">
            @if ($live)
                <div class="flex flex-wrap items-center gap-3">
                    <span class="badge-live"><span class="size-2 animate-pulse rounded-full bg-white"></span> {{ __('Live now') }}</span>
                    <h1 class="text-3xl font-extrabold sm:text-4xl">{{ $live->title }}</h1>
                </div>
                <div class="mt-8 grid gap-8 lg:grid-cols-3">
                    <div class="lg:col-span-2">
                        @if ($live->embed_url)
                            <x-video-embed :src="$live->embed_url" :title="$live->title" :watch-url="$live->watch_url" />
                        @else
                            <div class="flex aspect-video items-center justify-center rounded-(--radius-card) media-fallback p-8 text-center">
                                <div>
                                    <p class="text-lg">{{ __('This broadcast is available on the provider\'s site.') }}</p>
                                    @if ($live->watch_url)<a href="{{ $live->watch_url }}" class="btn-primary mt-4" rel="noopener" target="_blank">{{ __('Watch now') }}</a>@endif
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="space-y-4">
                        @if ($live->description)<p class="text-band-muted">{{ $live->description }}</p>@endif
                        <p class="flex items-center gap-2 text-sm text-band-muted"><x-glyph name="calendar" class="size-4" /> {{ local_time($live->scheduled_at, 'l j F Y, g:i a') }}</p>
                        @if ($live->watch_url)<a href="{{ $live->watch_url }}" class="btn-outline-band btn-sm" rel="noopener" target="_blank"><x-glyph name="youtube" class="size-4" /> {{ __('Open on :provider', ['provider' => ucfirst($live->provider)]) }}</a>@endif
                        @if ($live->chat_url)<a href="{{ $live->chat_url }}" class="btn-outline-band btn-sm" rel="noopener" target="_blank"><x-glyph name="chat" class="size-4" /> {{ __('Join the discussion') }}</a>@endif
                    </div>
                </div>
            @else
                <p class="eyebrow">{{ __('Live streaming') }}</p>
                <h1 class="mt-3 text-4xl font-extrabold sm:text-5xl">{{ __('We are offline right now') }}</h1>
                @if ($next)
                    <div class="mt-8 max-w-2xl rounded-(--radius-card) bg-band-card p-8 ring-1 ring-band-line">
                        <p class="eyebrow">{{ __('Next broadcast') }}</p>
                        <p class="mt-2 font-display text-2xl font-bold">{{ $next->title }}</p>
                        <p class="mt-3 flex items-center gap-2 text-band-muted"><x-glyph name="calendar" class="size-4" /> {{ local_time($next->scheduled_at, 'l j F Y, g:i a') }}</p>
                        @if ($next->description)<p class="mt-3 text-band-muted">{{ $next->description }}</p>@endif
                    </div>
                @else
                    <p class="mt-4 max-w-xl text-lg text-band-muted">{{ __('The next broadcast will be announced here. Meanwhile, enjoy recorded messages below.') }}</p>
                @endif
            @endif
        </div>
    </section>

    @if ($schedule->isNotEmpty())
        <section class="section">
            <div class="container-site">
                <h2 class="section-title">{{ __('Broadcast schedule') }}</h2>
                <ul class="mt-8 divide-y divide-line overflow-hidden rounded-(--radius-card) bg-surface">
                    @foreach ($schedule as $item)
                        <li class="flex flex-wrap items-center justify-between gap-2 px-6 py-4">
                            <span class="font-semibold text-fg">{{ $item->title }}</span>
                            <span class="text-sm text-muted">{{ local_time($item->scheduled_at, 'D j M, g:i a') }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    <section class="section {{ $schedule->isNotEmpty() ? 'pt-0' : '' }}">
        <div class="container-site">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <h2 class="section-title">{{ __('Recorded messages') }}</h2>
                <a href="{{ lroute('sermons.index') }}" class="btn-outline btn-sm">{{ __('Browse all sermons') }}</a>
            </div>
            @if ($sermons->isNotEmpty())
                <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">@foreach ($sermons as $sermon)<x-sermon-card :sermon="$sermon" />@endforeach</div>
            @else
                <div class="mt-8"><x-empty-state :title="__('No recordings yet')" icon="video" /></div>
            @endif
        </div>
    </section>
</x-layouts.app>
