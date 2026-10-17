<?php

namespace App\Filament\Resources\CounsellorProfiles\Pages;

use App\Filament\Resources\CounsellorProfiles\CounsellorProfileResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCounsellorProfiles extends ListRecords
{
    protected static string $resource = CounsellorProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
