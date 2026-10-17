<?php

namespace App\Filament\Resources\CounsellingServices\Pages;

use App\Filament\Resources\CounsellingServices\CounsellingServiceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCounsellingService extends EditRecord
{
    protected static string $resource = CounsellingServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
