<?php

namespace App\Filament\Resources\MemberContent\Pages;

use App\Filament\Resources\MemberContent\MemberContentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMemberContents extends ListRecords
{
    protected static string $resource = MemberContentResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
