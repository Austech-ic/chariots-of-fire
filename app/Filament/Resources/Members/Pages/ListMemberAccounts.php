<?php

namespace App\Filament\Resources\Members\Pages;

use App\Filament\Resources\Members\MemberAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMemberAccounts extends ListRecords
{
    protected static string $resource = MemberAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
