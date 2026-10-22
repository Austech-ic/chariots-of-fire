<x-layouts.app :title="$album->tr('title')" :description="$album->tr('description')" :image="$album->coverAsset()" :overlay-header="(bool) ($album->coverAsset())?->isPublic()">
    <x-page-hero :eyebrow="$album->taken_at?->translatedFormat('F Y') ?? __('Gallery')" :title="$album->tr('title')" :intro="$album->tr('description')" :image="$album->coverAsset()" />
    <section class="section pt-10">
        <div class="container-site">
            @if ($album->event)
                <p class="mb-6 text-sm text-muted">{{ __('From the programme') }} <a class="font-semibold text-accent underline" href="{{ lroute('events.show', $album->event) }}">{{ $album->event->tr('title') }}</a></p>
            @endif
            @php $images = $album->images->filter(fn ($i) => $i->mediaAsset?->isPublic()); @endphp
            @if ($images->isNotEmpty())
                <ul class="columns-1 gap-4 sm:columns-2 lg:columns-3 [&>li]:mb-4">
                    @foreach ($images as $image)
                        @php $alt = $image->alt_text ?: $image->mediaAsset->alt_text ?: ''; @endphp
                        <li class="break-inside-avoid">
                            <a href="{{ $image->mediaAsset->url('lg') }}" data-lightbox-item data-alt="{{ $alt }}" data-caption="{{ $image->caption }}" class="group block overflow-hidden rounded-(--radius-card)">
                                <x-picture :asset="$image->mediaAsset" :alt="$alt" imgClass="w-full transition duration-500 group-hover:scale-[1.03]" sizes="(min-width:1024px) 33vw, (min-width:640px) 50vw, 100vw" variant="md" />
                            </a>
                            @if ($image->caption)<p class="mt-2 text-sm text-muted">{{ $image->caption }}</p>@endif
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($videos->isNotEmpty())
                <h2 class="mt-12 text-2xl font-bold text-fg">{{ __('Videos') }}</h2>
                <div class="mt-6 grid gap-6 md:grid-cols-2">
                    @foreach ($videos as $video)<x-video-embed :src="$video->embedUrl" :title="$album->tr('title')" :watch-url="$video->watchUrl" />@endforeach
                </div>
            @endif

            @if ($images->isEmpty() && $videos->isEmpty())
                <x-empty-state :title="__('Photos coming soon')" icon="photo" />
            @endif

            <a href="{{ lroute('gallery.index') }}" class="btn-outline btn-sm mt-12"><x-glyph name="arrow-left" class="size-4" /> {{ __('All albums') }}</a>
        </div>
    </section>

    <div data-lightbox hidden role="dialog" aria-modal="true" aria-label="{{ __('Image viewer') }}" class="fixed inset-0 z-[60] flex items-center justify-center bg-ink/95 p-4">
        <figure class="max-h-full max-w-5xl">
            <img src="" alt="" class="max-h-[80vh] w-auto rounded-lg">
            <figcaption data-lightbox-caption class="mt-3 text-center text-sm text-white/80"></figcaption>
        </figure>
        <button type="button" data-lightbox-close class="absolute right-4 top-4 rounded-full bg-white/10 p-3 text-white hover:bg-white/20"><span class="sr-only">{{ __('Close') }}</span><x-glyph name="x" /></button>
        <button type="button" data-lightbox-prev class="absolute left-4 top-1/2 rounded-full bg-white/10 p-3 text-white hover:bg-white/20"><span class="sr-only">{{ __('Previous image') }}</span><x-glyph name="arrow-left" /></button>
        <button type="button" data-lightbox-next class="absolute right-4 top-1/2 rounded-full bg-white/10 p-3 text-white hover:bg-white/20"><span class="sr-only">{{ __('Next image') }}</span><x-glyph name="arrow-right" /></button>
    </div>
</x-layouts.app>
