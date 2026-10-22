<x-layouts.app :title="$mandate->tr('title')" :description="$mandate->tr('summary')" :image="$mandate->banner ?? $mandate->image" :overlay-header="(bool) ($mandate->banner ?? $mandate->image)?->isPublic()">
    <x-page-hero :eyebrow="__('Ministry mandate')" :title="$mandate->tr('title')" :intro="$mandate->tr('summary')" :image="$mandate->banner ?? $mandate->image" />
    <section class="section">
        <div class="container-site grid gap-12 lg:grid-cols-12">
            <article class="prose-content lg:col-span-8">{!! $mandate->tr('body') !!}</article>
            <aside class="space-y-6 lg:col-span-4">
                @if ($others->isNotEmpty())
                    <div class="card p-6">
                        <p class="eyebrow">{{ __('Other mandates') }}</p>
                        <ul class="mt-4 space-y-2">
                            @foreach ($others as $other)
                                <li><a href="{{ lroute('mandates.show', $other) }}" class="flex items-center justify-between font-semibold text-fg hover:text-accent">{{ $other->tr('title') }} <x-glyph name="arrow-right" class="size-4" /></a></li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="rounded-(--radius-card) bg-band p-6 text-band-fg">
                    <p class="font-display text-xl font-bold">{{ __('Get involved') }}</p>
                    <p class="mt-2 text-band-muted">{{ __('Talk to us about serving, partnering or attending.') }}</p>
                    <a href="{{ lroute('contact') }}" class="btn-primary mt-5">{{ __('Contact us') }}</a>
                </div>
            </aside>
        </div>
    </section>
    @if ($events->isNotEmpty())
        <section class="section bg-canvas-alt">
            <div class="container-site">
                <h2 class="section-title">{{ __('Upcoming programmes') }}</h2>
                <div class="mt-8 grid gap-6 md:grid-cols-3">@foreach ($events as $event)<x-event-card :event="$event" />@endforeach</div>
            </div>
        </section>
    @endif
</x-layouts.app>
