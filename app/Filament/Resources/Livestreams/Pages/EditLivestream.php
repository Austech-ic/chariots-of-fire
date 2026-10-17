<?php

namespace App\Filament\Resources\Livestreams\Pages;

use App\Filament\Resources\Livestreams\LivestreamResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLivestream extends EditRecord
{
    protected static string $resource = LivestreamResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
