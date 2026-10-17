<?php

namespace App\Filament\Resources\SermonCategories;

use App\Filament\Resources\BaseResource;
use App\Filament\Support\Fields;
use App\Models\SermonCategory;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class SermonCategoryResource extends BaseResource
{
    protected static ?string $model = SermonCategory::class;

    protected static string $permission = 'sermons.manage';

    protected static ?string $navigationLabel = 'Topics';

    protected static ?string $modelLabel = 'topic';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Watch & Listen';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Fields::slug(),
            Select::make('status')->options(['active' => 'Active', 'archived' => 'Archived'])->default('active'),
            Fields::translations([TextInput::make('name')->required()->maxLength(120)->columnSpanFull()], 'Name'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('translations')->withCount('sermons'))
            ->columns([
                Fields::translationColumn('name', 'Topic'),
                TextColumn::make('sermons_count')->label('Sermons'),
                TextColumn::make('status')->badge(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSermonCategories::route('/'),
            'create' => Pages\CreateSermonCategory::route('/create'),
            'edit' => Pages\EditSermonCategory::route('/{record}/edit'),
        ];
    }
}
