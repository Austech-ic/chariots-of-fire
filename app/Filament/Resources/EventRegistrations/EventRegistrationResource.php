<?php

namespace App\Filament\Resources\EventRegistrations;

use App\Filament\Resources\BaseResource;
use App\Models\Event;
use App\Models\EventRegistration;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class EventRegistrationResource extends BaseResource
{
    protected static ?string $model = EventRegistration::class;

    protected static string $permission = 'events.manage';

    protected static array $deniedActions = ['create'];

    protected static ?string $navigationLabel = 'Registrations';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Programmes';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(120),
            TextInput::make('email')->email()->required(),
            TextInput::make('phone')->tel(),
            TextInput::make('guests')->numeric()->minValue(0),
            Select::make('status')->options(['confirmed' => 'Confirmed', 'cancelled' => 'Cancelled', 'attended' => 'Attended'])->required(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('event.translations'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('registration_reference')->label('Reference')->searchable()->fontFamily('mono'),
                TextColumn::make('event')->state(fn (EventRegistration $r) => $r->event?->tr('title')),
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('guests'),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->since(),
            ])
            ->filters([
                SelectFilter::make('event_id')->label('Event')
                    ->options(fn () => Event::with('translations')->where('registration_enabled', true)->latest('starts_at')->get()->mapWithKeys(fn ($e) => [$e->id => $e->tr('title').' · '.local_time($e->starts_at, 'j M Y')])),
                SelectFilter::make('status')->options(['confirmed' => 'Confirmed', 'cancelled' => 'Cancelled', 'attended' => 'Attended']),
            ])
            ->headerActions([
                Action::make('export')->label('Export CSV')->icon(Heroicon::OutlinedArrowDownTray)
                    ->action(function ($livewire) {
                        $query = $livewire->getFilteredTableQuery()->with('event.translations');

                        return response()->streamDownload(function () use ($query) {
                            $out = fopen('php://output', 'w');
                            fputcsv($out, ['Reference', 'Event', 'Name', 'Email', 'Phone', 'Guests', 'Status', 'Registered at']);
                            $query->chunk(500, function ($rows) use ($out) {
                                foreach ($rows as $r) {
                                    fputcsv($out, [$r->registration_reference, $r->event?->tr('title'), $r->name, $r->email, $r->phone, $r->guests, $r->status, local_time($r->created_at, 'Y-m-d H:i')]);
                                }
                            });
                            fclose($out);
                        }, 'registrations-'.now()->format('Ymd-His').'.csv');
                    }),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEventRegistrations::route('/'),
            'edit' => Pages\EditEventRegistration::route('/{record}/edit'),
        ];
    }
}
