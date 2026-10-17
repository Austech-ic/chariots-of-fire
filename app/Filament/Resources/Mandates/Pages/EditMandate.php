<?php

namespace App\Filament\Resources\Mandates\Pages;

use App\Filament\Resources\Mandates\MandateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMandate extends EditRecord
{
    protected static string $resource = MandateResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
