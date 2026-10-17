<?php

namespace App\Filament\Resources\CounsellingServices;

use App\Filament\Resources\BaseResource;
use App\Filament\Support\Fields;
use App\Models\CounsellingService;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class CounsellingServiceResource extends BaseResource
{
    protected static ?string $model = CounsellingService::class;

    protected static string $permission = 'counselling.configure';

    protected static ?string $navigationLabel = 'Services';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static string|UnitEnum|null $navigationGroup = 'Counselling';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Service')->schema([
                TextInput::make('name')->required()->maxLength(120),
                Fields::slug(),
                Textarea::make('description')->rows(2)->columnSpanFull(),
                Select::make('status')->options(['active' => 'Active', 'inactive' => 'Inactive'])->default('active'),
                TextInput::make('sort_order')->numeric()->default(0),
            ])->columns(2),
            Section::make('Booking rules')->schema([
                Select::make('booking_mode')->options(CounsellingService::MODES)->default('both')->required(),
                Toggle::make('approval_required')->label('Self-service bookings need staff approval')->default(true)->inline(false),
                Toggle::make('requires_account')->label('Visitors must sign in to book')->inline(false),
                CheckboxList::make('appointment_methods')->label('Formats offered')->options(CounsellingService::METHODS)->required()->columns(3),
                TextInput::make('duration_minutes')->numeric()->minValue(15)->maxValue(240)->default(60)->suffix('min')->required(),
                TextInput::make('buffer_minutes')->label('Buffer between appointments')->numeric()->minValue(0)->maxValue(120)->default(15)->suffix('min'),
                TextInput::make('min_notice_hours')->label('Minimum notice')->numeric()->minValue(0)->default(24)->suffix('hours'),
                TextInput::make('max_advance_days')->label('Bookable up to')->numeric()->minValue(1)->default(30)->suffix('days ahead'),
                Select::make('counsellors')->relationship('counsellors', 'display_name')->multiple()->preload()->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('booking_mode')->formatStateUsing(fn ($state) => CounsellingService::MODES[$state] ?? $state),
                TextColumn::make('duration_minutes')->suffix(' min'),
                IconColumn::make('approval_required')->boolean(),
                TextColumn::make('status')->badge(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCounsellingServices::route('/'),
            'create' => Pages\CreateCounsellingService::route('/create'),
            'edit' => Pages\EditCounsellingService::route('/{record}/edit'),
        ];
    }
}
