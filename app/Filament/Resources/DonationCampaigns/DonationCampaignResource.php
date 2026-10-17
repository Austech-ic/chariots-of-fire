<?php

namespace App\Filament\Resources\DonationCampaigns;

use App\Filament\Resources\BaseResource;
use App\Filament\Support\Fields;
use App\Models\DonationCampaign;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class DonationCampaignResource extends BaseResource
{
    protected static ?string $model = DonationCampaign::class;

    protected static string $permission = 'donations.view';

    protected static ?string $navigationLabel = 'Giving purposes';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static string|UnitEnum|null $navigationGroup = 'Giving';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        $tz = config('app.display_timezone');

        return $schema->components([
            TextInput::make('name')->required()->maxLength(160),
            Fields::slug(),
            Textarea::make('description')->rows(2)->columnSpanFull(),
            Select::make('currency')->options(['NGN' => 'NGN', 'USD' => 'USD', 'GBP' => 'GBP', 'EUR' => 'EUR'])->default('NGN')->required(),
            TextInput::make('target_minor')->label('Target amount (optional)')->numeric()->minValue(0)
                ->formatStateUsing(fn ($state, $record) => $state ? $state / (10 ** Money::exponent($record?->currency ?? 'NGN')) : null)
                ->dehydrateStateUsing(fn ($state, $get) => $state === null || $state === '' ? null : Money::toMinor((string) $state, $get('currency') ?? 'NGN')),
            DateTimePicker::make('starts_at')->timezone($tz)->seconds(false),
            DateTimePicker::make('ends_at')->timezone($tz)->seconds(false)->after('starts_at'),
            Select::make('status')->options(['active' => 'Active', 'inactive' => 'Inactive'])->default('active'),
            TextInput::make('sort_order')->numeric()->default(0),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('raised')->state(fn (DonationCampaign $r) => Money::format($r->raisedMinor(), $r->currency)),
                TextColumn::make('target_minor')->label('Target')->formatStateUsing(fn ($state, DonationCampaign $r) => $state ? Money::format($state, $r->currency) : '—'),
                TextColumn::make('status')->badge(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDonationCampaigns::route('/'),
            'create' => Pages\CreateDonationCampaign::route('/create'),
            'edit' => Pages\EditDonationCampaign::route('/{record}/edit'),
        ];
    }
}
