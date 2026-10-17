<?php

namespace App\Filament\Resources\Events\Pages;

use App\Console\Commands\GenerateOccurrences;
use App\Filament\Resources\Events\EventResource;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate')->label('Generate upcoming dates')
                ->visible(fn () => filled($this->record->recurrence['freq'] ?? null) && ! $this->record->parent_event_id)
                ->action(function () {
                    $count = app(GenerateOccurrences::class)->expand($this->record->load('translations'), CarbonImmutable::now()->addDays(90));
                    Notification::make()->success()->title("{$count} date(s) created")->send();
                }),
            DeleteAction::make(),
        ];
    }
}
