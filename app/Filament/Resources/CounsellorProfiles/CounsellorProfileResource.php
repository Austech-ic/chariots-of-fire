<?php

namespace App\Filament\Resources\CounsellorProfiles;

use App\Filament\Resources\BaseResource;
use App\Models\AvailabilityRule;
use App\Models\CounsellorProfile;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class CounsellorProfileResource extends BaseResource
{
    protected static ?string $model = CounsellorProfile::class;

    protected static string $permission = 'counselling.configure';

    protected static ?string $navigationLabel = 'Counsellors & availability';

    protected static ?string $modelLabel = 'counsellor';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Counselling';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        $tz = config('app.display_timezone');

        return $schema->components([
            Section::make('Counsellor')->schema([
                Select::make('user_id')->label('Staff account')
                    ->options(fn () => User::where('is_staff', true)->orderBy('name')->pluck('name', 'id'))
                    ->searchable()->required()->unique(ignoreRecord: true)
                    ->helperText('Give this person the “Counsellor” role so they can read requests assigned to them.'),
                TextInput::make('display_name')->label('Name shown to visitors')->required()->maxLength(120),
                Textarea::make('bio')->rows(2)->columnSpanFull(),
                Select::make('status')->options(['active' => 'Active', 'inactive' => 'Inactive'])->default('active'),
                Toggle::make('booking_enabled')->label('Accepting bookings')->default(true)->inline(false),
                Select::make('services')->relationship('services', 'name')->multiple()->preload()->columnSpanFull(),
            ])->columns(2),
            Section::make('Weekly working hours')->schema([
                Repeater::make('availabilityRules')->relationship()->hiddenLabel()
                    ->schema([
                        Select::make('weekday')->options(AvailabilityRule::WEEKDAYS)->required(),
                        TimePicker::make('start_time')->required()->seconds(false),
                        TimePicker::make('end_time')->required()->seconds(false)->after('start_time'),
                        DatePicker::make('effective_from'),
                        DatePicker::make('effective_until'),
                    ])->columns(5)->defaultItems(0)->addActionLabel('Add working hours')
                    ->mutateRelationshipDataBeforeCreateUsing(fn (array $data) => $data + ['timezone' => config('app.display_timezone')]),
            ]),
            Section::make('Leave and blocked time')->schema([
                Repeater::make('availabilityExceptions')->relationship()->hiddenLabel()
                    ->schema([
                        DateTimePicker::make('starts_at')->required()->timezone($tz)->seconds(false),
                        DateTimePicker::make('ends_at')->required()->timezone($tz)->seconds(false)->after('starts_at'),
                        Select::make('exception_type')->options(['blocked' => 'Blocked', 'leave' => 'Leave'])->default('blocked'),
                        TextInput::make('reason')->maxLength(255),
                    ])->columns(4)->defaultItems(0)->addActionLabel('Add blocked period'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('display_name')->searchable(),
                TextColumn::make('user.email'),
                TextColumn::make('services.name')->listWithLineBreaks(),
                IconColumn::make('booking_enabled')->boolean(),
                TextColumn::make('status')->badge(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCounsellorProfiles::route('/'),
            'create' => Pages\CreateCounsellorProfile::route('/create'),
            'edit' => Pages\EditCounsellorProfile::route('/{record}/edit'),
        ];
    }
}
