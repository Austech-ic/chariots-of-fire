@props(['album'])
<a href="{{ lroute('gallery.show', $album) }}" class="group relative block aspect-[4/3] overflow-hidden rounded-(--radius-card)">
    <x-picture :asset="$album->coverAsset()" alt="" class="absolute inset-0" imgClass="h-full w-full object-cover transition duration-700 group-hover:scale-105" sizes="(min-width: 1024px) 25vw, 50vw" variant="sm" />
    <div class="absolute inset-0 bg-gradient-to-t from-ink/90 via-ink/20 to-transparent"></div>
    <div class="absolute inset-x-0 bottom-0 p-5">
        @if ($album->taken_at)<p class="text-xs font-medium text-white/70">{{ $album->taken_at->translatedFormat('F Y') }}</p>@endif
        <h3 class="mt-1 text-lg font-bold text-white">{{ $album->tr('title') }}</h3>
    </div>
</a>
