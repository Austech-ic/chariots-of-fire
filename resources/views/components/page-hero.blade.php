@props(['title', 'eyebrow' => null, 'intro' => null, 'image' => null])
<section class="relative isolate overflow-hidden bg-ink pb-16 pt-36 text-white sm:pb-20 sm:pt-44">
    @if ($image)
        <x-picture :asset="$image" alt="" class="absolute inset-0 -z-10 opacity-40" eager sizes="100vw" variant="lg" />
    @else
        <div class="absolute inset-0 -z-10 media-fallback"></div>
    @endif
    <div class="absolute inset-0 -z-10 bg-gradient-to-t from-ink via-ink/60 to-ink/30"></div>
    <div class="container-site animate-fade-in">
        @if ($eyebrow)<p class="eyebrow-light">{{ $eyebrow }}</p>@endif
        <h1 class="mt-3 max-w-3xl text-4xl font-extrabold sm:text-5xl lg:text-6xl">{{ $title }}</h1>
        @if ($intro)<p class="mt-5 max-w-2xl text-lg text-white/80">{{ $intro }}</p>@endif
        {{ $slot }}
    </div>
</section>
