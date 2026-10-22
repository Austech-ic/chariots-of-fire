@php $t = $page?->translation(); @endphp
<x-layouts.app :title="$t?->meta_title ?? $t?->title ?? __('Our founder')" :description="$t?->meta_description ?? $t?->summary" :image="$page?->image">
    <x-page-hero :eyebrow="__('Our founder')" :title="$t?->title ?? __('Our founder')" :intro="$t?->summary" />
    <section class="section">
        <div class="container-site grid gap-12 lg:grid-cols-12">
            <div class="lg:col-span-4">
                <x-picture :asset="$page?->image" :alt="$t?->title" class="aspect-[4/5] rounded-(--radius-card) lg:sticky lg:top-28" sizes="(min-width:1024px) 33vw, 100vw" />
            </div>
            <article class="prose-content lg:col-span-8">
                @if ($t?->body)
                    {!! $t->body !!}
                @else
                    <p>{{ __('The founder\'s biography will be published here once approved by the ministry.') }}</p>
                @endif
            </article>
        </div>
    </section>
    @if ($sermons->isNotEmpty())
        <section class="section bg-canvas-alt">
            <div class="container-site">
                <h2 class="section-title">{{ __('Selected teachings') }}</h2>
                <div class="mt-8 grid gap-6 md:grid-cols-3">@foreach ($sermons as $sermon)<x-sermon-card :sermon="$sermon" />@endforeach</div>
            </div>
        </section>
    @endif
</x-layouts.app>
