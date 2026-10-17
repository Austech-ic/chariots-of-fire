<?php

namespace App\Filament\Resources\MediaAssets\Pages;

use App\Filament\Resources\MediaAssets\MediaAssetResource;
use App\Services\Media\MediaLibrary;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class CreateMediaAsset extends CreateRecord
{
    protected static string $resource = MediaAssetResource::class;

    /** Validate the stored file by its real contents and queue processing. */
    protected function handleRecordCreation(array $data): Model
    {
        $path = is_array($data['path']) ? reset($data['path']) : $data['path'];
        $original = is_array($data['original_name'] ?? null) ? reset($data['original_name']) : ($data['original_name'] ?? null);

        try {
            return app(MediaLibrary::class)->register($path, [
                'original_name' => $original ? mb_substr($original, 0, 250) : basename($path),
                'visibility' => $data['visibility'] ?? 'private',
                'alt_text' => $data['alt_text'] ?? null,
                'caption' => $data['caption'] ?? null,
                'credit' => $data['credit'] ?? null,
                'usage_permission' => $data['usage_permission'] ?? null,
            ]);
        } catch (InvalidArgumentException $e) {
            Notification::make()->danger()->title('Upload rejected')->body($e->getMessage())->send();

            throw new Halt;
        }
    }
}
