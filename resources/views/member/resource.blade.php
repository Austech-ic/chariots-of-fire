<x-member-layout :title="$resource->title">
    <div class="card p-6">
        @if ($resource->description)<div class="prose-content text-base">{!! nl2br(e($resource->description)) !!}</div>@endif
        <div class="mt-6 flex flex-wrap gap-3">
            @if ($resource->asset)<a href="{{ route('member.resources.download', $resource) }}" class="btn-dark"><x-glyph name="download" class="size-4" /> {{ __('Download') }} ({{ $resource->asset->humanSize() }})</a>@endif
            @if ($resource->external_url)<a href="{{ $resource->external_url }}" class="btn-outline" rel="noopener" target="_blank">{{ __('Open link') }}</a>@endif
        </div>
    </div>
    <a href="{{ route('member.resources') }}" class="btn-outline btn-sm mt-6"><x-glyph name="arrow-left" class="size-4" /> {{ __('All resources') }}</a>
</x-member-layout>
