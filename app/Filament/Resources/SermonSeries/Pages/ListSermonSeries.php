<?php

namespace App\Filament\Resources\SermonSeries\Pages;

use App\Filament\Resources\SermonSeries\SermonSeriesResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSermonSeries extends ListRecords
{
    protected static string $resource = SermonSeriesResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
