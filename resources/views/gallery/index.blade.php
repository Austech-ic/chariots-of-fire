<x-layouts.app :title="__('Gallery')" :description="__('Outreach photographs and event highlights.')">
    <x-page-hero :eyebrow="__('Ministry Life')" :title="__('The work in pictures')" :intro="__('Outreach, worship, teaching and community — moments from the ministry.')" />
    <section class="section pt-10">
        <div class="container-site">
            @if ($categories->isNotEmpty())
                <nav class="flex flex-wrap gap-2" aria-label="{{ __('Album categories') }}">
                    <a href="{{ lroute('gallery.index') }}" class="badge px-4 py-2 {{ ! $category ? 'bg-fg text-canvas' : 'bg-surface text-fg' }}">{{ __('All') }}</a>
                    @foreach ($categories as $cat)
                        <a href="{{ lroute('gallery.index', ['category' => $cat]) }}" class="badge px-4 py-2 {{ $category === $cat ? 'bg-fg text-canvas' : 'bg-surface text-fg' }}">{{ $cat }}</a>
                    @endforeach
                </nav>
            @endif
            @if ($albums->isNotEmpty())
                <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">@foreach ($albums as $album)<x-album-card :album="$album" />@endforeach</div>
                <div class="mt-10">{{ $albums->links() }}</div>
            @else
                <div class="mt-8"><x-empty-state :title="__('No albums published yet')" icon="photo" /></div>
            @endif
        </div>
    </section>
</x-layouts.app>
