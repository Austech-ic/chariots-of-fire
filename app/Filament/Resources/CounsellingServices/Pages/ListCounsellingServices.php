<?php

namespace App\Filament\Resources\CounsellingServices\Pages;

use App\Filament\Resources\CounsellingServices\CounsellingServiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCounsellingServices extends ListRecords
{
    protected static string $resource = CounsellingServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
