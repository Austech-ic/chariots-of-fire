<?php

namespace App\Filament\Resources\Livestreams;

use App\Filament\Resources\BaseResource;
use App\Models\Event;
use App\Models\Livestream;
use App\Models\Sermon;
use App\Services\Streaming\StreamManager;
use App\Support\Audit;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class LivestreamResource extends BaseResource
{
    protected static ?string $model = Livestream::class;

    protected static string $permission = 'livestreams.manage';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    protected static string|UnitEnum|null $navigationGroup = 'Watch & Listen';

    protected static ?int $navigationSort = 0;

    public static function getNavigationBadge(): ?string
    {
        return Livestream::where('status', 'live')->exists() ? 'LIVE' : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Broadcast')->schema([
                TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),
                Textarea::make('description')->rows(3)->columnSpanFull(),
                DateTimePicker::make('scheduled_at')->label('Scheduled start')->required()->timezone(config('app.display_timezone'))->seconds(false),
                Select::make('event_id')->label('Programme')
                    ->options(fn () => Event::with('translations')->latest('starts_at')->limit(100)->get()->mapWithKeys(fn ($e) => [$e->id => $e->tr('title').' · '.local_time($e->starts_at, 'j M Y')]))
                    ->searchable(),
            ])->columns(2),
            Section::make('Provider')->schema([
                Select::make('provider')->options(fn () => app(StreamManager::class)->options())->default('youtube')->required()->live(),
                TextInput::make('external_id')->label('Stream URL or ID')->maxLength(255)
                    ->helperText('Paste the YouTube live/watch link, a Vimeo event link, or an approved embed URL.')
                    ->rule(fn ($get) => function ($attr, $value, $fail) use ($get) {
                        if ($value && ! app(StreamManager::class)->resolve($get('provider'), (string) $value)) {
                            $fail('This link is not valid for the selected provider (or its host is not approved).');
                        }
                    }),
                TextInput::make('chat_url')->label('Discussion / chat link')->url()->maxLength(255),
                Select::make('recording_sermon_id')->label('Recording (sermon)')
                    ->options(fn () => Sermon::with('translations')->latest('preached_at')->limit(100)->get()->mapWithKeys(fn ($s) => [$s->id => $s->tr('title')]))
                    ->searchable(),
            ])->columns(2),
            Section::make('Status')->schema([
                Radio::make('status_source')->label('Who decides the live status?')->options([
                    'manual' => 'An administrator sets it here',
                    'provider' => 'Check the provider automatically (needs a YouTube API key)',
                ])->default('manual'),
                Select::make('status')->options(Livestream::STATUSES)->default('scheduled')->required()
                    ->helperText('The website only shows “Live” when this says Live now.'),
                Toggle::make('is_published')->label('Show on website')->default(true)->inline(false),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('scheduled_at', 'desc')
            ->columns([
                TextColumn::make('title')->searchable()->limit(50),
                TextColumn::make('provider')->badge()->color('gray'),
                TextColumn::make('scheduled_at')->dateTime('D j M Y, H:i', config('app.display_timezone'))->sortable(),
                TextColumn::make('status')->badge()->formatStateUsing(fn ($state) => Livestream::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) { 'live' => 'danger', 'ended' => 'gray', 'cancelled' => 'warning', default => 'info' }),
                TextColumn::make('status_source')->label('Status from')->color('gray'),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('goLive')->label('Mark as live')->icon(Heroicon::OutlinedSignal)->color('danger')->requiresConfirmation()
                        ->visible(fn (Livestream $r) => $r->status !== 'live')
                        ->action(function (Livestream $r) {
                            $r->update(['status' => 'live', 'status_source' => 'manual']);
                            Audit::log('marked_live', $r);
                        }),
                    Action::make('end')->label('End broadcast')->icon(Heroicon::OutlinedStopCircle)->requiresConfirmation()
                        ->visible(fn (Livestream $r) => $r->status === 'live')
                        ->action(fn (Livestream $r) => $r->update(['status' => 'ended', 'ended_at' => now()])),
                    Action::make('check')->label('Check provider status')->icon(Heroicon::OutlinedArrowPath)
                        ->action(function (Livestream $r) {
                            $result = app(StreamManager::class)->syncStatus($r);
                            Notification::make()->title('Provider says: '.$result->status)->body($result->error)->{$result->error ? 'warning' : 'success'}()->send();
                        }),
                ]),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLivestreams::route('/'),
            'create' => Pages\CreateLivestream::route('/create'),
            'edit' => Pages\EditLivestream::route('/{record}/edit'),
        ];
    }
}
