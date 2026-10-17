<?php

namespace App\Filament\Resources\AccountRequests\Pages;

use App\Filament\Resources\AccountRequests\AccountRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAccountRequests extends ListRecords
{
    protected static string $resource = AccountRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
