<?php

namespace App\Filament\Resources\WebhookEvents;

use App\Filament\Resources\BaseResource;
use App\Models\WebhookEvent;
use BackedEnum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class WebhookEventResource extends BaseResource
{
    protected static ?string $model = WebhookEvent::class;

    protected static string $permission = 'audit.view';

    protected static array $deniedActions = ['create', 'update', 'delete', 'deleteAny'];

    protected static ?string $navigationLabel = 'Webhook log';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 9;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('received_at')->dateTime('j M Y H:i:s', config('app.display_timezone')),
                TextColumn::make('provider')->badge(),
                TextColumn::make('event_type'),
                TextColumn::make('external_event_id')->limit(30)->fontFamily('mono'),
                TextColumn::make('status')->badge()->color(fn ($state) => match ($state) { 'processed' => 'success', 'failed' => 'danger', default => 'gray' }),
                TextColumn::make('error')->limit(40)->placeholder('—'),
            ])
            ->filters([SelectFilter::make('status')->options(['received' => 'Received', 'processed' => 'Processed', 'ignored' => 'Ignored', 'failed' => 'Failed'])]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListWebhookEvents::route('/')];
    }
}
