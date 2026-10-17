<?php

namespace App\Filament\Resources\Livestreams\Pages;

use App\Filament\Resources\Livestreams\LivestreamResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLivestream extends CreateRecord
{
    protected static string $resource = LivestreamResource::class;
}
