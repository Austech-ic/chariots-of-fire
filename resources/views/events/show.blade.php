@php
    $t = $event->translation();
    $start = $event->localStart();
    $end = $event->localEnd();
    $stream = $event->livestreams->first();
@endphp
<x-layouts.app :title="$t?->title" :description="$t?->summary" :image="$event->banner">
    @push('head')
        <script type="application/ld+json">{!! json_encode(array_filter([
            '@context' => 'https://schema.org', '@type' => 'Event', 'name' => $t?->title,
            'startDate' => $event->starts_at->toIso8601String(), 'endDate' => $event->ends_at?->toIso8601String(),
            'eventStatus' => 'https://schema.org/'.match ($event->lifecycle) { 'cancelled' => 'EventCancelled', 'postponed' => 'EventPostponed', default => 'EventScheduled' },
            'eventAttendanceMode' => 'https://schema.org/'.match ($event->event_type) { 'online' => 'OnlineEventAttendanceMode', 'hybrid' => 'MixedEventAttendanceMode', default => 'OfflineEventAttendanceMode' },
            'location' => $t?->venue_name ? ['@type' => 'Place', 'name' => $t->venue_name, 'address' => $t->venue_address] : null,
            'description' => $t?->summary,
        ]), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    @endpush

    <x-page-hero :eyebrow="$event->category ?? __('Programme')" :title="$t?->title" :intro="$t?->summary" :image="$event->banner" />

    <section class="section">
        <div class="container-site grid gap-12 lg:grid-cols-12">
            <div class="lg:col-span-8">
                @if ($event->lifecycle !== 'scheduled')
                    <div role="alert" class="mb-8 rounded-xl border p-5 {{ $event->isCancelled() ? 'border-ember/40 bg-ember/5' : 'border-gold/50 bg-gold/10' }}">
                        <p class="font-bold text-ink">{{ $event->isCancelled() ? __('This programme has been cancelled.') : __('This programme has been postponed.') }}</p>
                        @if ($t?->notice)<p class="mt-1 text-body">{{ $t->notice }}</p>@endif
                    </div>
                @endif
                <x-flash />
                @if ($t?->description)<div class="prose-content">{!! $t->description !!}</div>@endif
                @if ($t?->agenda)
                    <h2 class="mt-12 text-2xl font-bold text-ink">{{ __('Agenda') }}</h2>
                    <div class="mt-4 whitespace-pre-line rounded-(--radius-card) bg-white p-6 text-body">{{ $t->agenda }}</div>
                @endif
                @if ($t?->speakers)
                    <h2 class="mt-12 text-2xl font-bold text-ink">{{ __('Speakers') }}</h2>
                    <p class="mt-3 text-lg text-body">{{ $t->speakers }}</p>
                @endif
                @if ($stream && $stream->isLive() && $stream->embed_url)
                    <h2 class="mt-12 text-2xl font-bold text-ink">{{ __('Watch live') }}</h2>
                    <div class="mt-4"><x-video-embed :src="$stream->embed_url" :title="$stream->title" :watch-url="$stream->watch_url" /></div>
                @endif
            </div>

            <aside class="space-y-6 lg:col-span-4">
                <div class="card p-6">
                    <dl class="space-y-4 text-sm">
                        <div class="flex gap-3"><x-glyph name="calendar" class="size-5 text-gold-700" /><div><dt class="font-semibold text-ink">{{ __('Date') }}</dt><dd class="text-muted">{{ $start->translatedFormat('l j F Y') }}</dd></div></div>
                        <div class="flex gap-3"><x-glyph name="clock" class="size-5 text-gold-700" /><div><dt class="font-semibold text-ink">{{ __('Time') }}</dt><dd class="text-muted">{{ $start->format('g:i a') }}@if ($end) – {{ $end->format('g:i a') }}@endif <span class="block text-xs">{{ __('West Africa Time (:tz)', ['tz' => $event->timezone]) }}</span></dd></div></div>
                        @if ($event->event_type !== 'online' && ($t?->venue_name || $t?->venue_address))
                            <div class="flex gap-3"><x-glyph name="map-pin" class="size-5 text-gold-700" /><div><dt class="font-semibold text-ink">{{ __('Venue') }}</dt><dd class="text-muted">{{ $t->venue_name }}@if ($t->venue_address)<span class="block">{{ $t->venue_address }}</span>@endif @if ($event->map_url)<a href="{{ $event->map_url }}" class="mt-1 block font-semibold text-gold-700 underline" rel="noopener" target="_blank">{{ __('Directions') }}</a>@endif</dd></div></div>
                        @endif
                        @if ($event->event_type !== 'in_person')
                            <div class="flex gap-3"><x-glyph name="video" class="size-5 text-gold-700" /><div><dt class="font-semibold text-ink">{{ __('Online') }}</dt><dd class="text-muted">
                                @if ($event->online_url)<a href="{{ $event->online_url }}" class="font-semibold text-gold-700 underline" rel="noopener" target="_blank">{{ __('Join online') }}</a>@else<a href="{{ lroute('live') }}" class="font-semibold text-gold-700 underline">{{ __('Live streaming page') }}</a>@endif
                            </dd></div></div>
                        @endif
                    </dl>
                    <a href="{{ lroute('events.ics', $event) }}" class="btn-outline btn-sm mt-6 w-full"><x-glyph name="calendar" class="size-4" /> {{ __('Add to calendar') }}</a>
                    @if (! $event->isCancelled())
                        @php $gcal = 'https://calendar.google.com/calendar/render?action=TEMPLATE&text='.urlencode((string) $t?->title).'&dates='.$event->starts_at->utc()->format('Ymd\THis\Z').'/'.($event->ends_at ?? $event->starts_at->copy()->addHours(2))->utc()->format('Ymd\THis\Z').'&location='.urlencode((string) $t?->venue_name); @endphp
                        <a href="{{ $gcal }}" class="mt-2 block text-center text-xs font-semibold text-gold-700 underline" rel="noopener" target="_blank">{{ __('Add to Google Calendar') }}</a>
                    @endif
                </div>

                @if ($event->acceptsRegistrations())
                    <div class="card p-6">
                        <h2 class="text-xl font-bold text-ink">{{ __('Register') }}</h2>
                        @if ($seatsLeft !== null)<p class="mt-1 text-sm text-muted">{{ trans_choice(':count place left|:count places left', $seatsLeft, ['count' => $seatsLeft]) }}</p>@endif
                        @if ($t?->registration_instructions)<p class="mt-3 text-sm text-body">{{ $t->registration_instructions }}</p>@endif
                        @if ($seatsLeft === 0)
                            <p class="mt-4 font-semibold text-ember">{{ __('This programme is fully booked.') }}</p>
                        @else
                            <form method="POST" action="{{ lroute('events.register', $event) }}" class="relative mt-5 space-y-4" data-submit-once>
                                @csrf <x-honeypot />
                                <x-field name="name" :label="__('Full name')" required autocomplete="name" :value="auth()->user()?->name" />
                                <x-field name="email" type="email" :label="__('Email')" required autocomplete="email" :value="auth()->user()?->email" />
                                <x-field name="phone" type="tel" :label="__('Phone (optional)')" autocomplete="tel" />
                                <x-field name="guests" type="number" :label="__('Additional guests')" min="0" max="10" value="0" />
                                <button class="btn-dark w-full">{{ __('Register') }}</button>
                            </form>
                        @endif
                    </div>
                @elseif ($event->external_registration_url && ! $event->isCancelled())
                    <a href="{{ $event->external_registration_url }}" class="btn-primary w-full" rel="noopener" target="_blank">{{ __('Register') }}</a>
                @endif
            </aside>
        </div>
    </section>
</x-layouts.app>
