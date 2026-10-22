@php $t = $page?->translation(); @endphp
<x-layouts.app :title="$t?->meta_title ?? $t?->title ?? __('About us')" :description="$t?->meta_description ?? $t?->summary" :image="$page?->image" :overlay-header="(bool) ($page?->image)?->isPublic()">
    <x-page-hero :eyebrow="__('About us')" :title="$t?->title ?? setting('site_name')" :intro="$t?->summary" :image="$page?->image" />

    @if ($t?->body)
        <section class="section">
            <div class="container-site max-w-3xl prose-content">{!! $t->body !!}</div>
        </section>
    @endif

    @if ($mandates->isNotEmpty())
        <section id="mandates" class="section bg-band text-band-fg scroll-mt-20">
            <div class="container-site">
                <p class="eyebrow">{{ __('Ministry mandates') }}</p>
                <h2 class="mt-3 text-3xl font-extrabold sm:text-4xl">{{ __('Areas of service') }}</h2>
                <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($mandates as $mandate)
                        <a href="{{ lroute('mandates.show', $mandate) }}" class="group block overflow-hidden rounded-(--radius-card) bg-band-card ring-1 ring-band-line transition hover:ring-gold/60">
                            <x-picture :asset="$mandate->image" alt="" class="aspect-[16/9]" variant="sm" sizes="(min-width:1024px) 33vw, 100vw" />
                            <div class="p-6">
                                <h3 class="flex items-center gap-2 text-xl font-bold group-hover:text-accent"><x-glyph :name="$mandate->icon ?: 'flame'" class="size-5 text-accent" /> {{ $mandate->tr('title') }}</h3>
                                <p class="mt-2 text-band-muted">{{ $mandate->tr('summary') }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($events->isNotEmpty() || $sermons->isNotEmpty())
        <section class="section">
            <div class="container-site space-y-16">
                @if ($sermons->isNotEmpty())
                    <div>
                        <h2 class="section-title">{{ __('Recent messages') }}</h2>
                        <div class="mt-8 grid gap-6 md:grid-cols-3">@foreach ($sermons as $sermon)<x-sermon-card :sermon="$sermon" />@endforeach</div>
                    </div>
                @endif
                @if ($events->isNotEmpty())
                    <div>
                        <h2 class="section-title">{{ __('Upcoming programmes') }}</h2>
                        <div class="mt-8 grid gap-6 md:grid-cols-3">@foreach ($events as $event)<x-event-card :event="$event" />@endforeach</div>
                    </div>
                @endif
            </div>
        </section>
    @endif

    <section class="section bg-canvas-alt">
        <div class="container-site flex flex-col items-start justify-between gap-6 md:flex-row md:items-center">
            <div>
                <h2 class="section-title">{{ __('Get involved') }}</h2>
                <p class="mt-2 text-lg text-muted">{{ __('Join a programme, request prayer and counselling, or partner with the ministry.') }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ lroute('contact') }}" class="btn-dark">{{ __('Contact us') }}</a>
                <a href="{{ lroute('give') }}" class="btn-primary">{{ __('Give') }}</a>
            </div>
        </div>
    </section>
</x-layouts.app>
