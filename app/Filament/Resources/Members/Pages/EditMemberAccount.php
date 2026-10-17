<?php

namespace App\Filament\Resources\Members\Pages;

use App\Filament\Resources\Members\MemberAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMemberAccount extends EditRecord
{
    protected static string $resource = MemberAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
