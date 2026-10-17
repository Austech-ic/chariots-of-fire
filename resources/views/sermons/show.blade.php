@php
    $yt = $sermon->youtube();
    $audio = $sermon->audio();
    $video = $sermon->video();
    $url = lroute('sermons.show', $sermon);
@endphp
<x-layouts.app :title="$sermon->tr('title')" :description="$sermon->tr('description')" :image="$sermon->thumbnail">
    @push('head')
        <script type="application/ld+json">{!! json_encode(array_filter([
            '@context' => 'https://schema.org', '@type' => $yt || $video ? 'VideoObject' : 'AudioObject',
            'name' => $sermon->tr('title'), 'description' => \Illuminate\Support\Str::limit(strip_tags((string) $sermon->tr('description')), 300) ?: $sermon->tr('title'),
            'uploadDate' => $sermon->preached_at?->toDateString(), 'thumbnailUrl' => $sermon->thumbnail?->isPublic() ? $sermon->thumbnail->url('md') : ($yt ? "https://i.ytimg.com/vi/{$yt->external_id}/hqdefault.jpg" : null),
            'embedUrl' => $yt?->is_embeddable ? $yt->embed_url : null,
        ]), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    @endpush

    <section class="bg-ink pb-12 pt-32 text-white sm:pt-36">
        <div class="container-site">
            <a href="{{ lroute('sermons.index') }}" class="inline-flex items-center gap-2 text-sm text-white/70 hover:text-white"><x-glyph name="arrow-left" class="size-4" /> {{ __('All sermons') }}</a>
            <div class="mt-6 grid gap-10 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    @if ($yt && $yt->is_embeddable)
                        <x-video-embed :src="$yt->embed_url" :title="$sermon->tr('title')" :watch-url="$yt->watch_url" />
                    @elseif ($yt)
                        <div class="flex aspect-video flex-col items-center justify-center rounded-(--radius-card) media-fallback p-8 text-center">
                            <p class="text-lg">{{ __('This video cannot be played here.') }}</p>
                            <a href="{{ $yt->watch_url }}" class="btn-primary mt-4" rel="noopener" target="_blank">{{ __('Watch on YouTube') }}</a>
                        </div>
                    @elseif ($video && $video->asset?->isPublic())
                        <video controls preload="metadata" class="aspect-video w-full rounded-(--radius-card) bg-black" @if ($sermon->thumbnail?->isPublic()) poster="{{ $sermon->thumbnail->url('lg') }}" @endif>
                            <source src="{{ $video->asset->url() }}" type="{{ $video->asset->mime_type }}">
                        </video>
                    @else
                        <x-picture :asset="$sermon->thumbnail" alt="" class="aspect-video rounded-(--radius-card)" eager />
                    @endif

                    @if ($audio && $audio->asset?->isPublic())
                        <div class="mt-6 rounded-(--radius-card) bg-ink-800 p-5 ring-1 ring-white/10">
                            <p class="mb-3 flex items-center gap-2 text-sm font-semibold text-gold"><x-glyph name="speaker" class="size-4" /> {{ __('Listen to the audio') }}</p>
                            <audio controls preload="none" class="w-full"><source src="{{ $audio->asset->url() }}" type="{{ $audio->asset->mime_type }}">{{ __('Your browser does not support audio playback.') }}</audio>
                        </div>
                    @endif
                </div>
                <div>
                    @if ($sermon->series)<a href="{{ lroute('sermons.index', ['series' => $sermon->series->slug]) }}" class="eyebrow-light hover:underline">{{ $sermon->series->tr('title') }}</a>@endif
                    <h1 class="mt-2 text-3xl font-extrabold leading-tight sm:text-4xl">{{ $sermon->tr('title') }}</h1>
                    <dl class="mt-6 space-y-3 text-sm">
                        @if ($sermon->speakers->isNotEmpty())<div class="flex gap-2"><dt class="sr-only">{{ __('Speaker') }}</dt><x-glyph name="user" class="size-4 text-gold" /><dd>{{ $sermon->speakers->pluck('name')->join(', ') }}</dd></div>@endif
                        @if ($sermon->preached_at)<div class="flex gap-2"><dt class="sr-only">{{ __('Date') }}</dt><x-glyph name="calendar" class="size-4 text-gold" /><dd>{{ $sermon->preached_at->translatedFormat('l j F Y') }}</dd></div>@endif
                        @if ($sermon->tr('scripture_references'))<div class="flex gap-2"><dt class="sr-only">{{ __('Scripture') }}</dt><x-glyph name="book-open" class="size-4 text-gold" /><dd>{{ $sermon->tr('scripture_references') }}</dd></div>@endif
                    </dl>
                    @if ($sermon->categories->isNotEmpty())
                        <div class="mt-5 flex flex-wrap gap-2">
                            @foreach ($sermon->categories as $cat)<a href="{{ lroute('sermons.index', ['topic' => $cat->slug]) }}" class="badge bg-white/10 text-white hover:bg-gold hover:text-ink">{{ $cat->tr('name') }}</a>@endforeach
                        </div>
                    @endif
                    <div class="mt-6 flex flex-wrap gap-2">
                        @foreach ($sermon->media->where('download_allowed', true) as $file)
                            @if ($file->asset?->isPublic())
                                <a href="{{ $file->asset->downloadUrl() }}" class="btn-outline-light btn-sm"><x-glyph name="download" class="size-4" /> {{ __('Download :type', ['type' => __(ucfirst($file->media_type))]) }}</a>
                            @endif
                        @endforeach
                        <button type="button" class="btn-outline-light btn-sm" data-share="{{ $url }}" data-title="{{ $sermon->tr('title') }}" data-copied="{{ __('Link copied') }}"><x-glyph name="share" class="size-4" /> <span data-share-label>{{ __('Share') }}</span></button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if ($sermon->tr('description'))
        <section class="section">
            <div class="container-site max-w-3xl prose-content">{!! nl2br(e($sermon->tr('description'))) !!}</div>
        </section>
    @endif

    @if ($related->isNotEmpty())
        <section class="section bg-ivory-200/60">
            <div class="container-site">
                <h2 class="section-title">{{ __('Related messages') }}</h2>
                <div class="mt-8 grid gap-6 md:grid-cols-3">@foreach ($related as $item)<x-sermon-card :sermon="$item" />@endforeach</div>
            </div>
        </section>
    @endif
</x-layouts.app>
