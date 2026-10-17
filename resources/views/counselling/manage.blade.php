<x-layouts.app :title="__('Your appointment')" solid-header noindex>
    <section class="section pt-36">
        <div class="container-site max-w-2xl">
            <x-flash />
            @error('appointment')<div role="alert" class="mb-6 rounded-xl bg-ember/5 p-4 text-ink">{{ $message }}</div>@enderror
            <div class="card p-8">
                <p class="eyebrow">{{ __('Appointment') }} · <span class="font-mono">{{ $appointment->reference }}</span></p>
                <h1 class="mt-3 text-3xl font-extrabold text-ink">{{ $appointment->service->name }}</h1>
                <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div><dt class="text-sm font-semibold text-muted">{{ __('When') }}</dt><dd class="text-lg font-semibold text-ink">{{ $appointment->localStart()->translatedFormat('l j F Y, g:i a') }}</dd></div>
                    <div><dt class="text-sm font-semibold text-muted">{{ __('Format') }}</dt><dd class="text-lg font-semibold text-ink">{{ __(\App\Models\CounsellingService::METHODS[$appointment->method] ?? $appointment->method) }}</dd></div>
                    <div><dt class="text-sm font-semibold text-muted">{{ __('Counsellor') }}</dt><dd class="text-lg font-semibold text-ink">{{ $appointment->counsellor->display_name }}</dd></div>
                    <div><dt class="text-sm font-semibold text-muted">{{ __('Status') }}</dt><dd class="text-lg font-semibold text-ink">{{ __(\App\Models\Appointment::STATUSES[$appointment->status]) }}</dd></div>
                </dl>

                @if ($appointment->status === 'offered' && $appointment->starts_at->isFuture())
                    <div class="mt-8 flex flex-wrap gap-3 border-t border-ink/10 pt-6">
                        <form method="POST" action="{{ route('appointments.accept', $appointment->reference) }}">@csrf <input type="hidden" name="token" value="{{ $token }}"><button class="btn-dark">{{ __('Accept this time') }}</button></form>
                        <form method="POST" action="{{ route('appointments.decline', $appointment->reference) }}" class="flex flex-wrap gap-2">@csrf <input type="hidden" name="token" value="{{ $token }}">
                            <label for="note" class="sr-only">{{ __('Note') }}</label>
                            <input id="note" name="note" class="form-input w-56 py-2" placeholder="{{ __('Suggest another time (optional)') }}">
                            <button class="btn-outline">{{ __('Ask for another time') }}</button>
                        </form>
                    </div>
                @elseif ($appointment->isChangeable())
                    <form method="POST" action="{{ route('appointments.cancel', $appointment->reference) }}" class="mt-8 space-y-3 border-t border-ink/10 pt-6">
                        @csrf <input type="hidden" name="token" value="{{ $token }}">
                        <label for="reason" class="form-label">{{ __('Need to cancel?') }}</label>
                        <input id="reason" name="reason" class="form-input" placeholder="{{ __('Reason (optional)') }}">
                        <button class="btn-outline">{{ __('Cancel appointment') }}</button>
                        <p class="text-sm text-muted">{{ __('To reschedule, cancel and book a new time, or contact the ministry office.') }}</p>
                    </form>
                @endif
            </div>
        </div>
    </section>
</x-layouts.app>
