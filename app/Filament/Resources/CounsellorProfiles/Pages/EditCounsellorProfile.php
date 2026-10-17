<?php

namespace App\Filament\Resources\CounsellorProfiles\Pages;

use App\Filament\Resources\CounsellorProfiles\CounsellorProfileResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCounsellorProfile extends EditRecord
{
    protected static string $resource = CounsellorProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
