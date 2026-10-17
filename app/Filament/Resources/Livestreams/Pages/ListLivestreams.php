<?php

namespace App\Filament\Resources\Livestreams\Pages;

use App\Filament\Resources\Livestreams\LivestreamResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLivestreams extends ListRecords
{
    protected static string $resource = LivestreamResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
