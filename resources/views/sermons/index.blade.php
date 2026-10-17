<x-layouts.app :title="__('Sermons')" :description="__('Search audio and video messages by topic, speaker, series and date.')">
    <x-page-hero :eyebrow="__('Watch & Listen')" :title="__('Sermons & series')" :intro="__('Messages that build faith — search by topic, speaker, series, language or date.')">
        <form method="GET" action="{{ lroute('sermons.index') }}" role="search" class="mt-8 flex max-w-xl gap-2">
            <label for="sermon-q" class="sr-only">{{ __('Search sermons') }}</label>
            <input id="sermon-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('Search titles, scriptures or speakers') }}" class="min-w-0 flex-1 rounded-full border-0 bg-white px-5 py-3 text-ink placeholder:text-muted/70 focus:ring-2 focus:ring-gold">
            <button class="btn-primary"><x-glyph name="search" class="size-4" /><span class="sr-only sm:not-sr-only">{{ __('Search') }}</span></button>
        </form>
    </x-page-hero>

    <section class="section pt-10">
        <div class="container-site">
            @if ($featured)
                @php $fyt = $featured->youtube(); @endphp
                <a href="{{ lroute('sermons.show', $featured) }}" class="card-link group mb-12 grid overflow-hidden md:grid-cols-2">
                    <div class="relative aspect-video md:aspect-auto">
                        @if ($featured->thumbnail)
                            <x-picture :asset="$featured->thumbnail" alt="" class="absolute inset-0" eager sizes="(min-width:768px) 50vw, 100vw" />
                        @elseif ($fyt)
                            <div data-media class="absolute inset-0 media-fallback"><img src="https://i.ytimg.com/vi/{{ $fyt->external_id }}/maxresdefault.jpg" alt="" class="h-full w-full object-cover" data-fallback></div>
                        @else
                            <div class="absolute inset-0 media-fallback"></div>
                        @endif
                    </div>
                    <div class="flex flex-col justify-center p-8 lg:p-12">
                        <p class="eyebrow">{{ __('Featured message') }}</p>
                        <h2 class="mt-3 text-3xl font-extrabold text-ink group-hover:text-gold-700">{{ $featured->tr('title') }}</h2>
                        <p class="mt-2 text-muted">{{ $featured->preached_at?->translatedFormat('j F Y') }} · {{ $featured->speakers->pluck('name')->join(', ') }}</p>
                        <p class="mt-4 text-body">{{ \Illuminate\Support\Str::limit($featured->tr('description'), 200) }}</p>
                        <span class="btn-dark mt-6 self-start"><x-glyph name="play" class="size-4" /> {{ __('Watch or listen') }}</span>
                    </div>
                </a>
            @endif

            @if ($featuredSeries->isNotEmpty())
                <div class="mb-12">
                    <h2 class="text-2xl font-bold text-ink">{{ __('Featured series') }}</h2>
                    <div class="mt-5 grid gap-4 sm:grid-cols-3">
                        @foreach ($featuredSeries as $s)
                            <a href="{{ lroute('sermons.index', ['series' => $s->slug]) }}" class="group relative block aspect-[16/9] overflow-hidden rounded-(--radius-card)">
                                <x-picture :asset="$s->image" alt="" class="absolute inset-0" variant="sm" />
                                <div class="absolute inset-0 bg-gradient-to-t from-ink/90 to-transparent"></div>
                                <p class="absolute bottom-4 left-4 right-4 font-display text-lg font-bold text-white">{{ $s->tr('title') }}</p>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="grid gap-8 lg:grid-cols-12">
                <form method="GET" action="{{ lroute('sermons.index') }}" class="space-y-5 lg:col-span-3" aria-label="{{ __('Filter sermons') }}">
                    <input type="hidden" name="q" value="{{ $filters['q'] ?? '' }}">
                    <div>
                        <label for="topic" class="form-label">{{ __('Topic') }}</label>
                        <select id="topic" name="topic" class="form-input" data-auto-submit>
                            <option value="">{{ __('All topics') }}</option>
                            @foreach ($topics as $topic)<option value="{{ $topic->slug }}" @selected(($filters['topic'] ?? '') === $topic->slug)>{{ $topic->tr('name') }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label for="speaker" class="form-label">{{ __('Speaker') }}</label>
                        <select id="speaker" name="speaker" class="form-input" data-auto-submit>
                            <option value="">{{ __('All speakers') }}</option>
                            @foreach ($speakers as $sp)<option value="{{ $sp->slug }}" @selected(($filters['speaker'] ?? '') === $sp->slug)>{{ $sp->name }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label for="series" class="form-label">{{ __('Series') }}</label>
                        <select id="series" name="series" class="form-input" data-auto-submit>
                            <option value="">{{ __('All series') }}</option>
                            @foreach ($series as $s)<option value="{{ $s->slug }}" @selected(($filters['series'] ?? '') === $s->slug)>{{ $s->tr('title') }}</option>@endforeach
                        </select>
                    </div>
                    @if (count($languages) > 1)
                        <div>
                            <label for="language" class="form-label">{{ __('Language') }}</label>
                            <select id="language" name="language" class="form-input" data-auto-submit>
                                <option value="">{{ __('All languages') }}</option>
                                @foreach ($languages as $code => $lang)<option value="{{ $code }}" @selected(($filters['language'] ?? '') === $code)>{{ $lang['native_name'] }}</option>@endforeach
                            </select>
                        </div>
                    @endif
                    <div>
                        <label for="type" class="form-label">{{ __('Media') }}</label>
                        <select id="type" name="type" class="form-input" data-auto-submit>
                            <option value="">{{ __('Audio and video') }}</option>
                            <option value="audio" @selected(($filters['type'] ?? '') === 'audio')>{{ __('Audio') }}</option>
                            <option value="video" @selected(($filters['type'] ?? '') === 'video')>{{ __('Video') }}</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div><label for="from" class="form-label">{{ __('From') }}</label><input id="from" type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-input px-2"></div>
                        <div><label for="to" class="form-label">{{ __('To') }}</label><input id="to" type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-input px-2"></div>
                    </div>
                    <div class="flex gap-2">
                        <button class="btn-dark btn-sm">{{ __('Apply filters') }}</button>
                        @if ($hasFilters)<a href="{{ lroute('sermons.index') }}" class="btn-outline btn-sm">{{ __('Reset') }}</a>@endif
                    </div>
                </form>

                <div class="lg:col-span-9">
                    <p class="mb-5 text-sm text-muted" aria-live="polite">{{ trans_choice(':count message|:count messages', $sermons->total(), ['count' => $sermons->total()]) }}</p>
                    @if ($sermons->isNotEmpty())
                        <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">@foreach ($sermons as $sermon)<x-sermon-card :sermon="$sermon" />@endforeach</div>
                        <div class="mt-10">{{ $sermons->links() }}</div>
                    @else
                        <x-empty-state :title="__('No messages match your search')" icon="search">
                            <a href="{{ lroute('sermons.index') }}" class="btn-dark btn-sm mt-4">{{ __('Clear search and filters') }}</a>
                        </x-empty-state>
                    @endif
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>
