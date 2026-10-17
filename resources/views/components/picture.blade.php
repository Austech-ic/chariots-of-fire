{{-- Responsive image from a MediaAsset with a designed fallback if it is missing or fails to load. --}}
@props(['asset' => null, 'alt' => null, 'sizes' => '100vw', 'class' => '', 'imgClass' => 'h-full w-full object-cover', 'eager' => false, 'variant' => 'md'])
<div data-media {{ $attributes->merge(['class' => trim('relative overflow-hidden '.$class.($asset && $asset->isPublic() ? '' : ' media-fallback'))]) }}>
    @if ($asset && $asset->isPublic())
        <img src="{{ $asset->url($variant) }}"
             @if ($asset->srcset()) srcset="{{ $asset->srcset() }}" sizes="{{ $sizes }}" @endif
             alt="{{ $alt ?? $asset->alt_text ?? '' }}"
             @if ($asset->width) width="{{ $asset->width }}" height="{{ $asset->height }}" @endif
             loading="{{ $eager ? 'eager' : 'lazy' }}" @if ($eager) fetchpriority="high" @endif decoding="async"
             class="{{ $imgClass }}" data-fallback>
    @endif
    {{ $slot }}
</div>
