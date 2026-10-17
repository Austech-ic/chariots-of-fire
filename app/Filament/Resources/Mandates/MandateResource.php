<?php

namespace App\Filament\Resources\Mandates;

use App\Filament\Resources\BaseResource;
use App\Filament\Support\Fields;
use App\Models\Mandate;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class MandateResource extends BaseResource
{
    protected static ?string $model = Mandate::class;

    protected static string $permission = 'content.edit';

    protected static ?string $navigationLabel = 'Ministry mandates';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 3;

    public const ICONS = ['flame' => 'Flame', 'book-open' => 'Book', 'hands' => 'Prayer hands', 'academic-cap' => 'Teaching', 'heart' => 'Heart', 'users' => 'People', 'globe' => 'Globe', 'chat' => 'Conversation'];

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Mandate')->schema([
                Fields::slug(),
                Select::make('icon')->options(self::ICONS)->default('flame'),
                Fields::media('image_asset_id', 'Card image'),
                Fields::media('banner_asset_id', 'Banner image'),
                TextInput::make('sort_order')->numeric()->default(0),
            ])->columns(2),
            Fields::translations([
                TextInput::make('title')->required()->maxLength(160)->columnSpanFull(),
                Textarea::make('summary')->rows(2)->maxLength(500)->columnSpanFull(),
                RichEditor::make('body')->columnSpanFull(),
            ]),
            Fields::publishing(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('translations'))
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                Fields::translationColumn(),
                TextColumn::make('slug')->color('gray'),
                Fields::statusColumn(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMandates::route('/'),
            'create' => Pages\CreateMandate::route('/create'),
            'edit' => Pages\EditMandate::route('/{record}/edit'),
        ];
    }
}
