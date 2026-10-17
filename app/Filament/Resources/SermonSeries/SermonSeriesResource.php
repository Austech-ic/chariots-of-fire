<?php

namespace App\Filament\Resources\SermonSeries;

use App\Filament\Resources\BaseResource;
use App\Filament\Support\Fields;
use App\Models\SermonSeries;
use BackedEnum;
use Filament\Actions\EditAction;
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

class SermonSeriesResource extends BaseResource
{
    protected static ?string $model = SermonSeries::class;

    protected static string $permission = 'sermons.manage';

    protected static ?string $modelLabel = 'series';

    protected static ?string $pluralModelLabel = 'series';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|UnitEnum|null $navigationGroup = 'Watch & Listen';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                Fields::slug(),
                Fields::media('image_asset_id', 'Artwork'),
                Toggle::make('is_featured')->label('Featured on the sermons page')->inline(false),
            ])->columns(2),
            Fields::translations([
                TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),
                Textarea::make('description')->rows(3)->columnSpanFull(),
            ]),
            Fields::publishing(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('translations')->withCount('sermons'))
            ->columns([
                Fields::translationColumn(),
                TextColumn::make('sermons_count')->label('Sermons'),
                IconColumn::make('is_featured')->label('Featured')->boolean(),
                Fields::statusColumn(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSermonSeries::route('/'),
            'create' => Pages\CreateSermonSeries::route('/create'),
            'edit' => Pages\EditSermonSeries::route('/{record}/edit'),
        ];
    }
}
