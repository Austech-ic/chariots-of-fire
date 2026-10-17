<?php

namespace App\Filament\Resources\MemberContent\Pages;

use App\Filament\Resources\MemberContent\MemberContentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMemberContent extends EditRecord
{
    protected static string $resource = MemberContentResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
