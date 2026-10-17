<x-layouts.app>
    {{-- SECTION 2 · CINEMATIC SLIDESHOW --}}
    <section data-slideshow aria-roledescription="carousel" aria-label="{{ __('Featured') }}" class="relative isolate h-[100svh] min-h-[560px] overflow-hidden bg-ink text-white">
        @forelse ($slides as $i => $slide)
            @php $t = $slide->translation(); @endphp
            <div data-slide data-duration="{{ $slide->duration_seconds }}" role="group" aria-roledescription="slide" aria-label="{{ $i + 1 }} / {{ $slides->count() }}"
                 class="absolute inset-0 transition-opacity duration-1000 {{ $i === 0 ? 'opacity-100' : 'opacity-0 pointer-events-none' }}" @if ($i > 0) aria-hidden="true" @endif>
                <x-picture :asset="$slide->image" alt="" class="absolute inset-0 -z-10" :eager="$i === 0" sizes="100vw" variant="lg" />
                <div class="absolute inset-0 -z-10 bg-gradient-to-r from-ink/90 via-ink/55 to-ink/10"></div>
                <div class="absolute inset-0 -z-10 bg-gradient-to-t from-ink/80 via-transparent to-ink/40"></div>
                <div class="container-site flex h-full flex-col justify-end pb-28 sm:justify-center sm:pb-0">
                    <div class="max-w-2xl {{ $i === 0 ? 'animate-fade-in' : '' }}">
                        @if ($i === 0)
                            <h1 class="text-4xl font-extrabold leading-[1.05] sm:text-6xl lg:text-7xl">{{ $t?->headline }}</h1>
                        @else
                            <p class="font-display text-4xl font-extrabold leading-[1.05] sm:text-6xl lg:text-7xl">{{ $t?->headline }}</p>
                        @endif
                        @if ($t?->description)<p class="mt-6 max-w-xl text-lg text-white/85 sm:text-xl">{{ $t->description }}</p>@endif
                        <div class="mt-8 flex flex-wrap gap-3">
                            @if ($t?->button_label && $t?->button_url)<a href="{{ localize_url($t->button_url) }}" class="btn-primary">{{ $t->button_label }}</a>@endif
                            @if ($t?->secondary_label && $t?->secondary_url)<a href="{{ localize_url($t->secondary_url) }}" class="btn-outline-light">{{ $t->secondary_label }}</a>@endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="absolute inset-0 -z-10 media-fallback"></div>
            <div class="container-site flex h-full flex-col justify-center">
                <h1 class="max-w-3xl text-5xl font-extrabold leading-[1.05] sm:text-7xl animate-fade-in">{{ setting('tagline') }}</h1>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ lroute('live') }}" class="btn-primary">{{ __('Watch Live') }}</a>
                    <a href="{{ lroute('about') }}" class="btn-outline-light">{{ __('Explore the Ministry') }}</a>
                </div>
            </div>
        @endforelse

        @if ($slides->count() > 1)
            <div class="container-site absolute inset-x-0 bottom-8 flex items-center justify-between">
                <div class="flex gap-2">
                    @foreach ($slides as $i => $slide)
                        <button type="button" data-slide-dot aria-current="{{ $i === 0 ? 'true' : 'false' }}" class="h-1.5 w-8 rounded-full bg-white/30 transition aria-[current=true]:w-14 aria-[current=true]:bg-gold">
                            <span class="sr-only">{{ __('Show slide :n', ['n' => $i + 1]) }}</span>
                        </button>
                    @endforeach
                </div>
                <div class="flex gap-2">
                    <button type="button" data-slide-prev class="rounded-full border border-white/30 p-3 hover:bg-white/10"><span class="sr-only">{{ __('Previous slide') }}</span><x-glyph name="arrow-left" /></button>
                    <button type="button" data-slide-next class="rounded-full border border-white/30 p-3 hover:bg-white/10"><span class="sr-only">{{ __('Next slide') }}</span><x-glyph name="arrow-right" /></button>
                </div>
            </div>
        @endif
    </section>

    @foreach ($sections as $section)
        @php $st = $section->translation(); @endphp
        @switch($section->key)
            @case('intro')
                {{-- SECTION 3 · MINISTRY INTRODUCTION --}}
                <section class="section">
                    <div class="container-site grid gap-12 lg:grid-cols-12">
                        <div class="lg:col-span-5">
                            <p class="eyebrow">{{ $st?->eyebrow }}</p>
                            <h2 class="section-title mt-3">{{ $st?->heading }}</h2>
                            @if ($st?->body)<p class="mt-5 text-lg text-muted">{{ $st->body }}</p>@endif
                            @if ($st?->link_label)<a href="{{ localize_url($st->link_url) }}" class="mt-6 inline-flex items-center gap-2 font-semibold text-gold-700 hover:gap-3 transition-all">{{ $st->link_label }} <x-glyph name="arrow-right" class="size-4" /></a>@endif
                        </div>
                        @if ($mandates->isNotEmpty())
                            <ul class="grid gap-4 sm:grid-cols-2 lg:col-span-7">
                                @foreach ($mandates as $mandate)
                                    <li>
                                        <a href="{{ lroute('mandates.show', $mandate) }}" class="card-link group flex h-full gap-4 p-5">
                                            <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-ink text-gold"><x-glyph :name="$mandate->icon ?: 'flame'" /></span>
                                            <span>
                                                <span class="block font-display text-lg font-bold text-ink group-hover:text-gold-700">{{ $mandate->tr('title') }}</span>
                                                <span class="mt-1 block text-sm text-muted">{{ \Illuminate\Support\Str::limit($mandate->tr('summary'), 110) }}</span>
                                            </span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </section>
                @break

            @case('watch')
                {{-- SECTION 4 · WATCH & LISTEN --}}
                <section class="section bg-ink text-white">
                    <div class="container-site">
                        <div class="flex flex-wrap items-end justify-between gap-4">
                            <div>
                                <p class="eyebrow-light">{{ $st?->eyebrow }}</p>
                                <h2 class="mt-3 text-3xl font-extrabold sm:text-4xl">{{ $st?->heading }}</h2>
                            </div>
                            @if ($st?->link_label)<a href="{{ localize_url($st->link_url) }}" class="btn-outline-light btn-sm">{{ $st->link_label }}</a>@endif
                        </div>
                        <div class="mt-10 grid gap-6 lg:grid-cols-5">
                            <div class="relative overflow-hidden rounded-(--radius-card) bg-ink-800 p-8 ring-1 ring-white/10 lg:col-span-2">
                                @if ($liveStream)
                                    <span class="badge-live"><span class="size-2 animate-pulse rounded-full bg-white"></span> {{ __('Live now') }}</span>
                                    <h3 class="mt-4 text-2xl font-bold">{{ $liveStream->title }}</h3>
                                    <a href="{{ lroute('live') }}" class="btn-primary mt-6"><x-glyph name="play" class="size-4" /> {{ __('Join the broadcast') }}</a>
                                @elseif ($nextStream)
                                    <p class="eyebrow-light">{{ __('Next broadcast') }}</p>
                                    <h3 class="mt-3 text-2xl font-bold">{{ $nextStream->title }}</h3>
                                    <p class="mt-3 flex items-center gap-2 text-white/75"><x-glyph name="calendar" class="size-4" /> {{ local_time($nextStream->scheduled_at, 'l j F, g:i a') }}</p>
                                    <a href="{{ lroute('live') }}" class="btn-outline-light mt-6">{{ __('Live streaming page') }}</a>
                                @else
                                    <p class="eyebrow-light">{{ __('Live broadcast') }}</p>
                                    <h3 class="mt-3 text-2xl font-bold">{{ __('We are offline right now') }}</h3>
                                    <p class="mt-3 text-white/75">{{ __('Catch up on recent messages while you wait for the next broadcast.') }}</p>
                                    <a href="{{ lroute('sermons.index') }}" class="btn-outline-light mt-6">{{ __('Recorded messages') }}</a>
                                @endif
                                <x-glyph name="flame" class="pointer-events-none absolute -bottom-6 -right-6 size-40 text-white/5" />
                            </div>
                            <div class="grid gap-4 sm:grid-cols-3 lg:col-span-3">
                                @forelse ($sermons as $sermon)
                                    <a href="{{ lroute('sermons.show', $sermon) }}" class="group block">
                                        <div class="relative aspect-video overflow-hidden rounded-(--radius-card) sm:aspect-[4/5]">
                                            @if ($sermon->thumbnail)
                                                <x-picture :asset="$sermon->thumbnail" alt="" class="absolute inset-0" variant="sm" sizes="(min-width:1024px) 20vw, 100vw" />
                                            @elseif ($sermon->youtube())
                                                <div data-media class="absolute inset-0 media-fallback"><img src="https://i.ytimg.com/vi/{{ $sermon->youtube()->external_id }}/hqdefault.jpg" alt="" loading="lazy" class="h-full w-full object-cover" data-fallback></div>
                                            @else
                                                <div class="absolute inset-0 media-fallback"></div>
                                            @endif
                                            <div class="absolute inset-0 bg-gradient-to-t from-ink via-ink/30 to-transparent"></div>
                                            <div class="absolute inset-x-0 bottom-0 p-4">
                                                <p class="text-xs text-white/60">{{ $sermon->preached_at?->translatedFormat('j M Y') }}</p>
                                                <p class="mt-1 font-display font-bold leading-snug group-hover:text-gold">{{ $sermon->tr('title') }}</p>
                                            </div>
                                        </div>
                                    </a>
                                @empty
                                    <p class="text-white/70 sm:col-span-3">{{ __('New messages will appear here soon.') }}</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </section>
                @break

            @case('programmes')
                {{-- SECTION 5 · PROGRAMMES --}}
                <section class="section">
                    <div class="container-site">
                        <div class="flex flex-wrap items-end justify-between gap-4">
                            <div>
                                <p class="eyebrow">{{ $st?->eyebrow }}</p>
                                <h2 class="section-title mt-3">{{ $st?->heading }}</h2>
                            </div>
                            @if ($st?->link_label)<a href="{{ localize_url($st->link_url) }}" class="btn-outline btn-sm">{{ $st->link_label }}</a>@endif
                        </div>
                        @if ($events->isNotEmpty())
                            <div class="mt-10 grid gap-6 md:grid-cols-3">
                                @foreach ($events as $event)<x-event-card :event="$event" />@endforeach
                            </div>
                        @else
                            <div class="mt-10"><x-empty-state :title="__('No upcoming programmes yet')" icon="calendar">{{ __('Please check back soon.') }}</x-empty-state></div>
                        @endif
                    </div>
                </section>
                @break

            @case('gallery')
                {{-- SECTION 6 · MINISTRY LIFE --}}
                @if ($albums->isNotEmpty())
                    <section class="section bg-ivory-200/60">
                        <div class="container-site">
                            <div class="flex flex-wrap items-end justify-between gap-4">
                                <div>
                                    <p class="eyebrow">{{ $st?->eyebrow }}</p>
                                    <h2 class="section-title mt-3">{{ $st?->heading }}</h2>
                                </div>
                                @if ($st?->link_label)<a href="{{ localize_url($st->link_url) }}" class="btn-outline btn-sm">{{ $st->link_label }}</a>@endif
                            </div>
                            <div class="mt-10 grid grid-cols-2 gap-4 lg:grid-cols-4">
                                @foreach ($albums as $album)<x-album-card :album="$album" />@endforeach
                            </div>
                        </div>
                    </section>
                @endif
                @break

            @case('connect')
                {{-- SECTION 7 · CONNECT & GIVE --}}
                <section class="section">
                    <div class="container-site">
                        <div class="text-center">
                            <p class="eyebrow">{{ $st?->eyebrow }}</p>
                            <h2 class="section-title mt-3">{{ $st?->heading }}</h2>
                            @if ($st?->body)<p class="mx-auto mt-4 max-w-2xl text-lg text-muted">{{ $st->body }}</p>@endif
                        </div>
                        <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach ([
                                ['chat', __('Counselling'), __('Request confidential support or book an appointment.'), lroute('counselling.index')],
                                ['mail', __('Newsletter'), __('Receive ministry news and programme updates.'), lroute('newsletter.show')],
                                ['heart', __('Give'), __('Support the work of the ministry securely online.'), lroute('give')],
                                ['phone', __('Contact us'), __('Send a message to the ministry office.'), lroute('contact')],
                            ] as [$icon, $label, $text, $url])
                                <a href="{{ $url }}" class="card-link group p-6">
                                    <span class="flex size-11 items-center justify-center rounded-full bg-gold/15 text-gold-700 group-hover:bg-gold group-hover:text-ink"><x-glyph :name="$icon" /></span>
                                    <span class="mt-4 block font-display text-lg font-bold text-ink">{{ $label }}</span>
                                    <span class="mt-1 block text-sm text-muted">{{ $text }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </section>
                @break
        @endswitch
    @endforeach
</x-layouts.app>
