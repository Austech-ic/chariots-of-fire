<?php

namespace App\Filament\Resources\SermonSeries\Pages;

use App\Filament\Resources\SermonSeries\SermonSeriesResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSermonSeries extends EditRecord
{
    protected static string $resource = SermonSeriesResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
