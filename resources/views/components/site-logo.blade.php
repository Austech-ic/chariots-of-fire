{{--
    Site logo. Uses the logos uploaded in Site settings, or the built-in flame
    mark. In the header both logo variants are rendered and CSS shows the one
    that suits the current background (light header vs dark mode / photo).
--}}
@props(['context' => 'header'])
@php
    use App\Support\Branding;
    $name = (string) setting('site_name');
    $words = preg_split('/\s+/', trim($name)) ?: [$name];
    $line2 = count($words) > 1 ? array_pop($words) : null;
    $line1 = implode(' ', $words);
    $height = Branding::logoHeight();
    $light = Branding::logo();
    $onDark = Branding::logoOnDark();
    $onDarkOnly = $context === 'footer';
@endphp
<span class="flex items-center gap-3">
    @if ($light || $onDark)
        @if ($onDarkOnly)
            <img src="{{ $onDark->url() }}" alt="{{ Branding::showName() ? '' : $name }}" style="height: {{ $height }}px" class="w-auto" @if ($onDark->width) width="{{ (int) round($onDark->width * $height / max(1, $onDark->height)) }}" height="{{ $height }}" @endif>
        @else
            <img src="{{ $light->url() }}" alt="{{ Branding::showName() ? '' : $name }}" style="height: {{ $height }}px" class="logo-on-light w-auto">
            <img src="{{ $onDark->url() }}" alt="{{ Branding::showName() ? '' : $name }}" style="height: {{ $height }}px" class="logo-on-dark w-auto">
        @endif
    @else
        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-gold text-ink"><x-glyph name="flame" class="size-6" /></span>
    @endif
    @if (Branding::showName())
        <span class="font-display leading-none">
            <span class="block text-base font-extrabold uppercase tracking-wide">{{ $line1 }}</span>
            @if ($line2)
                <span class="{{ $context === 'footer' ? 'text-gold' : 'text-hdr-accent' }} block text-[0.65rem] font-semibold uppercase tracking-[0.35em]">{{ $line2 }}</span>
            @endif
        </span>
    @endif
</span>
