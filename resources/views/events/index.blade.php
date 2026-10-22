<x-layouts.app :title="__('Programmes')" :description="__('Upcoming programmes, events and past activities.')">
    <x-page-hero :eyebrow="__('Programmes')" :title="__('Upcoming programmes')" :intro="__('Services, conferences, prayer meetings and outreach — in person and online.')" />

    <section class="section pt-10">
        <div class="container-site">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <nav class="inline-flex rounded-full bg-surface p-1 shadow-sm" aria-label="{{ __('Event views') }}">
                    <a href="{{ lroute('events.index', array_filter(['category' => $category])) }}" class="rounded-full px-5 py-2 text-sm font-semibold {{ $view === 'upcoming' ? 'bg-fg text-canvas' : 'text-fg' }}" @if ($view === 'upcoming') aria-current="page" @endif>{{ __('Upcoming') }}</a>
                    <a href="{{ lroute('events.index', array_filter(['view' => 'past', 'category' => $category])) }}" class="rounded-full px-5 py-2 text-sm font-semibold {{ $view === 'past' ? 'bg-fg text-canvas' : 'text-fg' }}" @if ($view === 'past') aria-current="page" @endif>{{ __('Past events') }}</a>
                </nav>
                @if ($categories->isNotEmpty())
                    <form method="GET" action="{{ lroute('events.index') }}" class="flex items-center gap-2">
                        @if ($view === 'past')<input type="hidden" name="view" value="past">@endif
                        <label for="ev-cat" class="text-sm font-semibold text-fg">{{ __('Category') }}</label>
                        <select id="ev-cat" name="category" class="form-input w-auto py-2" data-auto-submit>
                            <option value="">{{ __('All') }}</option>
                            @foreach ($categories as $cat)<option value="{{ $cat }}" @selected($category === $cat)>{{ $cat }}</option>@endforeach
                        </select>
                        <noscript><button class="btn-dark btn-sm">{{ __('Filter') }}</button></noscript>
                    </form>
                @endif
            </div>

            @if ($featured)
                <a href="{{ lroute('events.show', $featured) }}" class="group relative mt-10 block overflow-hidden rounded-(--radius-card) bg-ink text-white">
                    <x-picture :asset="$featured->banner" alt="" class="absolute inset-0 opacity-50" sizes="100vw" variant="lg" />
                    <div class="absolute inset-0 bg-gradient-to-r from-ink via-ink/70 to-transparent"></div>
                    <div class="relative max-w-xl p-8 sm:p-12">
                        <p class="eyebrow-light">{{ __('Featured programme') }}</p>
                        <h2 class="mt-3 text-3xl font-extrabold sm:text-4xl group-hover:text-gold">{{ $featured->tr('title') }}</h2>
                        <p class="mt-3 flex items-center gap-2 text-white/80"><x-glyph name="calendar" class="size-4" /> {{ $featured->localStart()->translatedFormat('l j F Y, g:i a') }}</p>
                        @if ($featured->tr('summary'))<p class="mt-3 text-white/75">{{ $featured->tr('summary') }}</p>@endif
                        <span class="btn-primary mt-6">{{ __('View details') }}</span>
                    </div>
                </a>
            @endif

            @if ($events->isNotEmpty())
                <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">@foreach ($events as $event)<x-event-card :event="$event" />@endforeach</div>
                <div class="mt-10">{{ $events->links() }}</div>
            @else
                <div class="mt-10"><x-empty-state :title="$view === 'past' ? __('No past events to show') : __('No upcoming programmes yet')" icon="calendar">{{ __('Please check back soon or subscribe to our newsletter.') }}</x-empty-state></div>
            @endif
        </div>
    </section>
</x-layouts.app>
