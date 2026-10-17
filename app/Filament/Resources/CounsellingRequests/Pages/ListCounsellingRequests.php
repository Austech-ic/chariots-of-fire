<?php

namespace App\Filament\Resources\CounsellingRequests\Pages;

use App\Filament\Resources\CounsellingRequests\CounsellingRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCounsellingRequests extends ListRecords
{
    protected static string $resource = CounsellingRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
