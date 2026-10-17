<?php

namespace App\Filament\Resources\Staff\Pages;

use App\Filament\Resources\Staff\StaffUserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/** New staff choose their own password via an emailed link; none is ever set by an administrator. */
class CreateStaffUser extends CreateRecord
{
    protected static string $resource = StaffUserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data + ['password' => Str::random(48), 'is_staff' => true];
    }

    protected function afterCreate(): void
    {
        $this->record->forceFill(['is_staff' => true, 'email_verified_at' => now()])->save();
        Password::sendResetLink(['email' => $this->record->email]);
    }
}
