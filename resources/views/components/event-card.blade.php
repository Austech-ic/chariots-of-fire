@props(['event', 'dark' => false])
@php $start = $event->localStart(); @endphp
<a href="{{ lroute('events.show', $event) }}" class="{{ $dark ? 'block overflow-hidden rounded-(--radius-card) bg-ink-800 ring-1 ring-white/10 transition hover:ring-gold/60' : 'card-link' }} group">
    <div class="relative aspect-[16/9]">
        <x-picture :asset="$event->banner" alt="" class="absolute inset-0" sizes="(min-width: 1024px) 33vw, 100vw" variant="sm" />
        <div class="absolute left-4 top-4 rounded-xl bg-white px-3 py-2 text-center shadow">
            <span class="block text-[0.65rem] font-bold uppercase tracking-wider text-gold-700">{{ $start->translatedFormat('M') }}</span>
            <span class="block font-display text-2xl font-extrabold leading-none text-ink">{{ $start->format('j') }}</span>
        </div>
        @if ($event->lifecycle !== 'scheduled')
            <span class="badge absolute right-4 top-4 {{ $event->isCancelled() ? 'bg-ember text-white' : 'bg-gold text-ink' }}">{{ __(\App\Models\Event::LIFECYCLES[$event->lifecycle]) }}</span>
        @endif
    </div>
    <div class="p-5">
        <h3 class="text-lg font-bold leading-snug {{ $dark ? 'text-white group-hover:text-gold' : 'text-ink group-hover:text-gold-700' }}">{{ $event->tr('title') }}</h3>
        <p class="mt-2 flex items-center gap-2 text-sm {{ $dark ? 'text-white/70' : 'text-muted' }}"><x-glyph name="clock" class="size-4" /> {{ $start->translatedFormat('D j M, g:i a') }}</p>
        <p class="mt-1 flex items-center gap-2 text-sm {{ $dark ? 'text-white/70' : 'text-muted' }}">
            <x-glyph :name="$event->event_type === 'online' ? 'video' : 'map-pin'" class="size-4" />
            {{ $event->event_type === 'online' ? __('Online') : ($event->tr('venue_name') ?: __(\App\Models\Event::TYPES[$event->event_type])) }}
        </p>
    </div>
</a>
