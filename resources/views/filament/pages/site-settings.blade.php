<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}
        <x-filament::button type="submit">Save settings</x-filament::button>
    </form>
    <p class="text-sm text-gray-500">Integration credentials (payments, email, SMS, WhatsApp, YouTube) are kept in the server's <code>.env</code> file and never stored in the database.</p>
</x-filament-panels::page>
