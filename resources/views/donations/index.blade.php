<x-layouts.app :title="__('Give')" :description="__('Support the ministry securely online.')">
    <x-page-hero :eyebrow="__('Give')" :title="__('Partner with the ministry')" :intro="setting('giving_intro') ?? __('Your generosity supports evangelism, discipleship, outreach and community transformation.')" />
    <section class="section">
        <div class="container-site grid gap-12 lg:grid-cols-12">
            <div class="lg:col-span-7">
                <x-flash />
                @if (! $gatewayAvailable || empty($currencies))
                    <x-empty-state :title="__('Online giving is temporarily unavailable')" icon="heart">{{ __('Please contact the ministry office for other ways to give.') }}</x-empty-state>
                @else
                    <form method="POST" action="{{ lroute('give.store') }}" class="card relative space-y-6 p-6 sm:p-8" data-submit-once>
                        @csrf <x-honeypot />
                        @if ($campaigns->isNotEmpty())
                            <fieldset>
                                <legend class="form-label">{{ __('Purpose') }}</legend>
                                <div class="grid gap-2 sm:grid-cols-2">
                                    @foreach ($campaigns as $i => $campaign)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="campaign" value="{{ $campaign->slug }}" class="peer sr-only" @checked(old('campaign', $selectedCampaign ?? $campaigns->first()->slug) === $campaign->slug)>
                                            <span class="block rounded-xl border border-ink/15 bg-white p-4 peer-checked:border-ink peer-checked:ring-2 peer-checked:ring-gold peer-focus-visible:ring-2 peer-focus-visible:ring-gold">
                                                <span class="block font-semibold text-ink">{{ $campaign->name }}</span>
                                                @if ($campaign->description)<span class="mt-1 block text-sm text-muted">{{ \Illuminate\Support\Str::limit($campaign->description, 90) }}</span>@endif
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endif

                        <div>
                            <p class="form-label" id="amount-label">{{ __('Amount') }}</p>
                            @if ($presets)
                                <div class="mb-3 flex flex-wrap gap-2" role="group" aria-labelledby="amount-label">
                                    @foreach ($presets as $preset)
                                        <button type="button" data-amount-preset="{{ $preset }}" aria-pressed="false" class="rounded-full border border-ink/15 bg-white px-4 py-2 text-sm font-semibold text-ink aria-pressed:bg-ink aria-pressed:text-white">{{ number_format((float) $preset) }}</button>
                                    @endforeach
                                </div>
                            @endif
                            <div class="flex gap-2">
                                <label for="currency" class="sr-only">{{ __('Currency') }}</label>
                                <select id="currency" name="currency" class="form-input w-28">
                                    @foreach ($currencies as $c)<option value="{{ $c }}" @selected(old('currency') === $c)>{{ $c }}</option>@endforeach
                                </select>
                                <label for="amount" class="sr-only">{{ __('Amount') }}</label>
                                <input id="amount" data-amount-input name="amount" inputmode="decimal" required value="{{ old('amount') }}" placeholder="{{ __('Custom amount') }}" class="form-input flex-1" @error('amount') aria-invalid="true" @enderror>
                            </div>
                            @error('amount')<p class="form-error">{{ $message }}</p>@enderror
                        </div>

                        <label class="flex items-start gap-3 text-sm">
                            <input type="checkbox" name="is_anonymous" value="1" data-anonymous-toggle class="mt-1 size-4 accent-gold-700" @checked(old('is_anonymous'))>
                            <span><strong>{{ __('Give anonymously') }}</strong> — {{ __('your name will not appear in ministry acknowledgements. An email is still needed for your receipt unless you leave it blank.') }}</span>
                        </label>
                        <div data-donor-name><x-field name="donor_name" :label="__('Your name')" autocomplete="name" :value="auth()->user()?->name" /></div>
                        <x-field name="donor_email" type="email" :label="__('Email for your receipt')" autocomplete="email" :value="auth()->user()?->email" />
                        <x-field name="note" type="textarea" rows="2" :label="__('Note (optional)')" />

                        <button class="btn-primary w-full text-base"><x-glyph name="lock" class="size-4" /> {{ __('Continue to secure payment') }}</button>
                        <p class="text-center text-xs text-muted">{{ __('You will complete payment on our payment provider\'s secure page. We never see or store your card details.') }}</p>
                    </form>
                @endif
            </div>
            <aside class="space-y-6 lg:col-span-5">
                <div class="rounded-(--radius-card) bg-ink p-8 text-white">
                    <x-glyph name="heart" class="size-8 text-gold" />
                    <h2 class="mt-4 text-2xl font-bold">{{ __('Giving with confidence') }}</h2>
                    <ul class="mt-4 space-y-3 text-white/80">
                        <li class="flex gap-2"><x-glyph name="check" class="mt-0.5 size-4 shrink-0 text-gold" /> {{ __('Payments are processed by a licensed provider.') }}</li>
                        <li class="flex gap-2"><x-glyph name="check" class="mt-0.5 size-4 shrink-0 text-gold" /> {{ __('Your gift is recorded only after the provider confirms it.') }}</li>
                        <li class="flex gap-2"><x-glyph name="check" class="mt-0.5 size-4 shrink-0 text-gold" /> {{ __('A receipt is emailed to you after confirmation.') }}</li>
                        <li class="flex gap-2"><x-glyph name="check" class="mt-0.5 size-4 shrink-0 text-gold" /> {{ __('Members can view their giving history in the member portal.') }}</li>
                    </ul>
                </div>
            </aside>
        </div>
    </section>
</x-layouts.app>
