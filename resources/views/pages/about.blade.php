@php $t = $page?->translation(); @endphp
<x-layouts.app :title="$t?->meta_title ?? $t?->title ?? __('About us')" :description="$t?->meta_description ?? $t?->summary" :image="$page?->image">
    <x-page-hero :eyebrow="__('About us')" :title="$t?->title ?? setting('site_name')" :intro="$t?->summary" :image="$page?->image" />

    @if ($t?->body)
        <section class="section">
            <div class="container-site max-w-3xl prose-content">{!! $t->body !!}</div>
        </section>
    @endif

    @if ($mandates->isNotEmpty())
        <section id="mandates" class="section bg-ink text-white scroll-mt-20">
            <div class="container-site">
                <p class="eyebrow-light">{{ __('Ministry mandates') }}</p>
                <h2 class="mt-3 text-3xl font-extrabold sm:text-4xl">{{ __('Areas of service') }}</h2>
                <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($mandates as $mandate)
                        <a href="{{ lroute('mandates.show', $mandate) }}" class="group block overflow-hidden rounded-(--radius-card) bg-ink-800 ring-1 ring-white/10 transition hover:ring-gold/60">
                            <x-picture :asset="$mandate->image" alt="" class="aspect-[16/9]" variant="sm" sizes="(min-width:1024px) 33vw, 100vw" />
                            <div class="p-6">
                                <h3 class="flex items-center gap-2 text-xl font-bold group-hover:text-gold"><x-glyph :name="$mandate->icon ?: 'flame'" class="size-5 text-gold" /> {{ $mandate->tr('title') }}</h3>
                                <p class="mt-2 text-white/70">{{ $mandate->tr('summary') }}</p>
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

    <section class="section bg-ivory-200/60">
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
