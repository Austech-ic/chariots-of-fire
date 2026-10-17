<?php

namespace App\Filament\Resources\GalleryAlbums\Pages;

use App\Filament\Resources\GalleryAlbums\GalleryAlbumResource;
use App\Filament\Support\Fields;
use App\Services\Media\MediaLibrary;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use InvalidArgumentException;

class EditGalleryAlbum extends EditRecord
{
    protected static string $resource = GalleryAlbumResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('bulkUpload')->label('Bulk upload photos')->icon('heroicon-o-arrow-up-tray')
                ->schema([
                    Fields::upload('files', 'image')->label('Photos')->multiple()->maxFiles(100)->required()->reorderable()->storeFileNamesIn('names'),
                ])
                ->action(function (array $data) {
                    $library = app(MediaLibrary::class);
                    $order = (int) $this->record->images()->max('sort_order');
                    $added = 0;
                    $rejected = 0;
                    foreach ((array) $data['files'] as $path) {
                        try {
                            $asset = $library->register($path, ['original_name' => $data['names'][$path] ?? basename($path)]);
                            $this->record->images()->create(['media_asset_id' => $asset->id, 'sort_order' => ++$order]);
                            $added++;
                        } catch (InvalidArgumentException) {
                            $rejected++;
                        }
                    }
                    $this->record->syncMediaVisibility();
                    $this->refreshFormData(['images']);
                    Notification::make()->success()->title("{$added} photo(s) added".($rejected ? ", {$rejected} rejected" : ''))
                        ->body('Images are being optimised in the background.')->send();
                }),
            DeleteAction::make(),
        ];
    }
}
