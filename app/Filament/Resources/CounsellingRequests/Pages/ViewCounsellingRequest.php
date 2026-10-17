<?php

namespace App\Filament\Resources\CounsellingRequests\Pages;

use App\Filament\Resources\CounsellingRequests\CounsellingRequestResource;
use App\Support\Audit;
use Filament\Resources\Pages\ViewRecord;

class ViewCounsellingRequest extends ViewRecord
{
    protected static string $resource = CounsellingRequestResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);
        // Access to confidential records is itself recorded.
        Audit::log('viewed', $this->record);
    }

    protected function getHeaderActions(): array
    {
        return CounsellingRequestResource::workflowActions();
    }
}
