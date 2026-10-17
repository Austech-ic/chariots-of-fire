<x-auth-card :title="__('Choose a new password')">
    <form method="POST" action="{{ route('password.update') }}" class="space-y-5" data-submit-once>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-field name="email" type="email" :label="__('Email')" required autocomplete="username" :value="$email" />
        <x-field name="password" type="password" :label="__('New password')" required autocomplete="new-password" />
        <x-field name="password_confirmation" type="password" :label="__('Confirm password')" required autocomplete="new-password" />
        <button class="btn-dark w-full">{{ __('Reset password') }}</button>
    </form>
</x-auth-card>
