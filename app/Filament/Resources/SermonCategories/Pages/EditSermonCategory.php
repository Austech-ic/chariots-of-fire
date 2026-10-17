<?php

namespace App\Filament\Resources\SermonCategories\Pages;

use App\Filament\Resources\SermonCategories\SermonCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSermonCategory extends EditRecord
{
    protected static string $resource = SermonCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
