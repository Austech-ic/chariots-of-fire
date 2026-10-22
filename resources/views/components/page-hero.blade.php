@props(['title', 'eyebrow' => null, 'intro' => null, 'image' => null])
@php $photo = $image && $image->isPublic(); @endphp
@if ($photo)
    {{-- Photo hero: cinematic dark overlay with white text in both themes. --}}
    <section class="relative isolate overflow-hidden bg-ink pb-16 pt-36 text-white sm:pb-20 sm:pt-44">
        <x-picture :asset="$image" alt="" class="absolute inset-0 -z-10 opacity-40" eager sizes="100vw" variant="lg" fallback="media-fallback-dark" />
        <div class="absolute inset-0 -z-10 bg-gradient-to-t from-ink via-ink/60 to-ink/30"></div>
        <div class="container-site animate-fade-in">
            @if ($eyebrow)<p class="eyebrow-light">{{ $eyebrow }}</p>@endif
            <h1 class="mt-3 max-w-3xl text-4xl font-extrabold sm:text-5xl lg:text-6xl">{{ $title }}</h1>
            @if ($intro)<p class="mt-5 max-w-2xl text-lg text-white/80">{{ $intro }}</p>@endif
            {{ $slot }}
        </div>
    </section>
@else
    {{-- No photo: a warm band that follows the light/dark theme. --}}
    <section class="relative isolate overflow-hidden bg-band pb-14 pt-32 text-band-fg sm:pb-16 sm:pt-40">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(55%_90%_at_88%_0%,rgb(201_164_92/0.28),transparent_70%)]"></div>
        <x-glyph name="flame" class="pointer-events-none absolute -right-10 -top-6 -z-10 size-72 text-band-fg/[0.04]" />
        <div class="container-site animate-fade-in">
            @if ($eyebrow)<p class="eyebrow">{{ $eyebrow }}</p>@endif
            <h1 class="mt-3 max-w-3xl text-4xl font-extrabold text-band-fg sm:text-5xl lg:text-6xl">{{ $title }}</h1>
            @if ($intro)<p class="mt-5 max-w-2xl text-lg text-band-muted">{{ $intro }}</p>@endif
            {{ $slot }}
        </div>
    </section>
@endif
