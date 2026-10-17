{{-- Privacy-enhanced provider embed with a visible fallback link. --}}
@props(['src', 'title', 'watchUrl' => null])
<div class="overflow-hidden rounded-(--radius-card) bg-black shadow-xl">
    <div class="relative aspect-video">
        <iframe src="{{ $src }}" title="{{ $title }}" class="absolute inset-0 h-full w-full" loading="lazy"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen" allowfullscreen
                referrerpolicy="strict-origin-when-cross-origin"></iframe>
    </div>
    @if ($watchUrl)
        <p class="bg-ink px-4 py-2 text-right text-xs text-white/70">
            {{ __('Video not loading?') }} <a href="{{ $watchUrl }}" class="font-semibold text-gold underline" rel="noopener" target="_blank">{{ __('Watch on the provider\'s site') }}</a>
        </p>
    @endif
</div>
