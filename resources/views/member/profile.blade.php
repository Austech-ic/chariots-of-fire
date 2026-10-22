<x-member-layout :title="__('Profile & security')">
    <div class="space-y-6">
        <form method="POST" action="{{ route('member.profile.update') }}" class="card space-y-5 p-6">
            @csrf @method('PUT')
            <h2 class="text-lg font-bold text-fg">{{ __('Profile') }}</h2>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="name" :label="__('Full name')" required :value="$user->name" autocomplete="name" />
                <x-field name="email" type="email" :label="__('Email')" required :value="$user->email" autocomplete="email" :help="__('Changing your email requires verifying the new address.')" />
                <x-field name="phone" type="tel" :label="__('Phone')" :value="$user->phone" autocomplete="tel" />
                <div>
                    <label for="locale" class="form-label">{{ __('Preferred language') }}</label>
                    <select id="locale" name="locale" class="form-input">
                        @foreach (\App\Support\Locales::all() as $code => $lang)<option value="{{ $code }}" @selected($user->locale === $code)>{{ $lang['native_name'] }}</option>@endforeach
                    </select>
                </div>
            </div>
            <button class="btn-dark">{{ __('Save profile') }}</button>
        </form>
        <form method="POST" action="{{ route('member.password.update') }}" class="card space-y-5 p-6">
            @csrf @method('PUT')
            <h2 class="text-lg font-bold text-fg">{{ __('Change password') }}</h2>
            <x-field name="current_password" type="password" :label="__('Current password')" required autocomplete="current-password" />
            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="password" type="password" :label="__('New password')" required autocomplete="new-password" />
                <x-field name="password_confirmation" type="password" :label="__('Confirm new password')" required autocomplete="new-password" />
            </div>
            <p class="text-sm text-muted">{{ __('Changing your password signs you out on other devices.') }}</p>
            <button class="btn-dark">{{ __('Update password') }}</button>
        </form>
    </div>
</x-member-layout>
