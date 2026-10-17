<x-layouts.app :title="__('Counselling')" :description="__('Confidential counselling and prayer support.')">
    <x-page-hero :eyebrow="__('Connect')" :title="__('Counselling & pastoral care')" :intro="setting('counselling_intro') ?? __('Confidential, prayerful support. Request a conversation with our team or book an available appointment.')" />

    <section class="section">
        <div class="container-site grid gap-6 md:grid-cols-2">
            <div class="card flex flex-col p-8">
                <span class="flex size-12 items-center justify-center rounded-full bg-ink text-gold"><x-glyph name="chat" /></span>
                <h2 class="mt-5 text-2xl font-bold text-ink">{{ __('Request counselling') }}</h2>
                <p class="mt-3 flex-1 text-muted">{{ __('Tell us briefly how we can help and when you are available. A coordinator will review your request confidentially, match you with a counsellor and propose a time.') }}</p>
                <a href="{{ lroute('counselling.request') }}" class="btn-dark mt-6 self-start">{{ __('Send a confidential request') }}</a>
            </div>
            <div class="card flex flex-col p-8">
                <span class="flex size-12 items-center justify-center rounded-full bg-gold text-ink"><x-glyph name="calendar" /></span>
                <h2 class="mt-5 text-2xl font-bold text-ink">{{ __('Book an appointment') }}</h2>
                <p class="mt-3 flex-1 text-muted">{{ __('Choose a service and pick an available time. Some services are confirmed instantly; others are reviewed by our team first.') }}</p>
                <a href="{{ lroute('counselling.book') }}" class="btn-primary mt-6 self-start">{{ __('See available times') }}</a>
            </div>
        </div>
    </section>

    @if ($services->isNotEmpty())
        <section class="section pt-0">
            <div class="container-site">
                <h2 class="section-title">{{ __('Services') }}</h2>
                <div class="mt-8 grid gap-6 md:grid-cols-3">
                    @foreach ($services as $service)
                        <div class="rounded-(--radius-card) border border-ink/10 bg-white p-6">
                            <h3 class="text-lg font-bold text-ink">{{ $service->name }}</h3>
                            @if ($service->description)<p class="mt-2 text-sm text-muted">{{ $service->description }}</p>@endif
                            <p class="mt-4 flex flex-wrap gap-2 text-xs">
                                <span class="badge-muted">{{ trans_choice(':count minute|:count minutes', $service->duration_minutes, ['count' => $service->duration_minutes]) }}</span>
                                @foreach ($service->methods() as $method)<span class="badge-muted">{{ __(\App\Models\CounsellingService::METHODS[$method]) }}</span>@endforeach
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="section bg-ink text-white">
        <div class="container-site grid gap-10 md:grid-cols-2">
            <div>
                <p class="eyebrow-light">{{ __('Confidentiality') }}</p>
                <h2 class="mt-3 text-3xl font-extrabold">{{ __('Your privacy matters') }}</h2>
            </div>
            <div class="space-y-4 text-white/80">
                <p>{{ setting('counselling_confidentiality') ?? __('Requests are encrypted and can only be read by the coordinator and the counsellor assigned to you. Emails we send contain appointment logistics only — never what you shared with us.') }}</p>
                <p>{{ __('Our counselling is pastoral and spiritual in nature and is not a substitute for professional medical, psychological or emergency services. If you or someone else is in immediate danger, contact local emergency services.') }}</p>
            </div>
        </div>
    </section>
</x-layouts.app>
