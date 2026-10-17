<?php

namespace App\Filament\Resources\SermonCategories\Pages;

use App\Filament\Resources\SermonCategories\SermonCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSermonCategories extends ListRecords
{
    protected static string $resource = SermonCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
