<?php

namespace App\Filament\Resources\Events;

use App\Filament\Resources\BaseResource;
use App\Filament\Support\Fields;
use App\Models\Event;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class EventResource extends BaseResource
{
    protected static ?string $model = Event::class;

    protected static string $permission = 'events.manage';

    protected static ?string $navigationLabel = 'Events & programmes';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Programmes';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        $tz = config('app.display_timezone');

        return $schema->components([
            Section::make('When and where')->schema([
                Fields::slug(),
                Select::make('event_type')->options(Event::TYPES)->default('in_person')->required()->live(),
                DateTimePicker::make('starts_at')->required()->timezone($tz)->seconds(false),
                DateTimePicker::make('ends_at')->timezone($tz)->seconds(false)->after('starts_at'),
                TextInput::make('category')->maxLength(60)->datalist(fn () => Event::query()->whereNotNull('category')->distinct()->pluck('category')->all()),
                Fields::media('banner_asset_id', 'Banner image'),
                TextInput::make('online_url')->label('Online link')->url()->maxLength(255)->visible(fn (Get $get) => $get('event_type') !== 'in_person'),
                TextInput::make('map_url')->label('Map / directions link')->url()->maxLength(255)->visible(fn (Get $get) => $get('event_type') !== 'online'),
                Select::make('lifecycle')->label('Status')->options(Event::LIFECYCLES)->default('scheduled')->required()
                    ->helperText('Postponed or cancelled programmes show a notice to visitors.'),
                Toggle::make('featured')->inline(false),
            ])->columns(2),

            Section::make('Registration')->schema([
                Toggle::make('registration_enabled')->label('Accept registrations on this website')->live()->inline(false),
                TextInput::make('capacity')->numeric()->minValue(1)->visible(fn (Get $get) => $get('registration_enabled'))->helperText('Leave empty for unlimited.'),
                TextInput::make('external_registration_url')->label('…or external registration link')->url()->maxLength(255),
            ])->columns(3),

            Section::make('Repeats')->description('Recurring programmes generate individual dates automatically (up to 90 days ahead).')->schema([
                Select::make('recurrence.freq')->label('Repeat')->options(['' => 'Does not repeat', 'daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'])->live(),
                TextInput::make('recurrence.interval')->label('Every')->numeric()->minValue(1)->default(1)->suffix('period(s)')->visible(fn (Get $get) => filled($get('recurrence.freq'))),
                DatePicker::make('recurrence.until')->label('Until')->visible(fn (Get $get) => filled($get('recurrence.freq'))),
                TextInput::make('recurrence.count')->label('Or number of occurrences')->numeric()->minValue(2)->visible(fn (Get $get) => filled($get('recurrence.freq'))),
            ])->columns(4)->visible(fn (?Event $record) => ! $record?->parent_event_id)->collapsible(),

            Fields::translations([
                TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),
                Textarea::make('summary')->rows(2)->maxLength(500)->columnSpanFull(),
                RichEditor::make('description')->columnSpanFull(),
                Textarea::make('agenda')->rows(4),
                TextInput::make('speakers')->maxLength(255),
                TextInput::make('venue_name')->maxLength(255),
                TextInput::make('venue_address')->maxLength(255),
                Textarea::make('registration_instructions')->rows(2),
                TextInput::make('notice')->label('Postponement / cancellation notice')->maxLength(255),
            ]),
            Fields::publishing(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('translations')->withCount(['registrations' => fn ($r) => $r->where('status', 'confirmed')]))
            ->defaultSort('starts_at', 'desc')
            ->columns([
                Fields::translationColumn(),
                TextColumn::make('starts_at')->dateTime('D j M Y, H:i', config('app.display_timezone'))->sortable(),
                TextColumn::make('event_type')->label('Type')->formatStateUsing(fn ($state) => Event::TYPES[$state] ?? $state),
                TextColumn::make('lifecycle')->label('Status')->badge()->color(fn ($state) => match ($state) { 'cancelled' => 'danger', 'postponed' => 'warning', default => 'success' }),
                TextColumn::make('registrations_count')->label('Registered'),
                Fields::statusColumn()->label('Publication'),
            ])
            ->filters([
                SelectFilter::make('lifecycle')->options(Event::LIFECYCLES),
                TernaryFilter::make('upcoming')->queries(
                    true: fn ($query) => $query->where('starts_at', '>=', now()),
                    false: fn ($query) => $query->where('starts_at', '<', now()),
                ),
                TernaryFilter::make('occurrences')->label('Recurring occurrences')->nullable()->attribute('parent_event_id'),
            ])
            ->recordActions([
                Action::make('registrations')->icon(Heroicon::OutlinedUsers)->color('gray')
                    ->url(fn (Event $r) => \App\Filament\Resources\EventRegistrations\EventRegistrationResource::getUrl('index', ['filters' => ['event_id' => ['value' => $r->id]]]))
                    ->visible(fn (Event $r) => $r->registration_enabled),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}
