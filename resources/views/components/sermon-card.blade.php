@props(['sermon'])
@php $yt = $sermon->youtube(); @endphp
<a href="{{ lroute('sermons.show', $sermon) }}" class="card-link group">
    <div class="relative aspect-video">
        @if ($sermon->thumbnail)
            <x-picture :asset="$sermon->thumbnail" :alt="''" class="absolute inset-0" sizes="(min-width: 1024px) 33vw, 100vw" variant="sm" />
        @elseif ($yt)
            <div data-media class="absolute inset-0 overflow-hidden media-fallback">
                <img src="https://i.ytimg.com/vi/{{ $yt->external_id }}/hqdefault.jpg" alt="" loading="lazy" class="h-full w-full object-cover" data-fallback>
            </div>
        @else
            <div class="absolute inset-0 media-fallback flex items-center justify-center"><x-glyph name="flame" class="size-10 text-gold/60" /></div>
        @endif
        <span class="absolute bottom-3 left-3 inline-flex size-10 items-center justify-center rounded-full bg-white/90 text-ink shadow transition group-hover:bg-gold">
            <x-glyph :name="in_array('video', $sermon->mediaTypes()) ? 'play' : 'speaker'" class="size-4" />
        </span>
        @if ($sermon->members_only)
            <span class="badge absolute right-3 top-3 bg-ink/80 text-white"><x-glyph name="lock" class="size-3" /> {{ __('Members') }}</span>
        @endif
    </div>
    <div class="p-5">
        <p class="text-xs font-medium text-muted">{{ $sermon->preached_at?->translatedFormat('j M Y') }}@if ($sermon->speakers->isNotEmpty()) · {{ $sermon->speakers->pluck('name')->join(', ') }}@endif</p>
        <h3 class="mt-1.5 text-lg font-bold leading-snug text-ink group-hover:text-gold-700">{{ $sermon->tr('title') }}</h3>
        @if ($sermon->tr('scripture_references'))
            <p class="mt-1 text-sm text-muted">{{ $sermon->tr('scripture_references') }}</p>
        @endif
    </div>
</a>
