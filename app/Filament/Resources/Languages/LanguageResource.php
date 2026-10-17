<?php

namespace App\Filament\Resources\Languages;

use App\Filament\Resources\BaseResource;
use App\Models\Language;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class LanguageResource extends BaseResource
{
    protected static ?string $model = Language::class;

    protected static string $permission = 'translations.manage';

    protected static array $deniedActions = ['delete', 'deleteAny'];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->required()->maxLength(5)->regex('/^[a-z]{2}(-[a-z]{2})?$/')->unique(ignoreRecord: true)->helperText('ISO code, e.g. fr, yo, pt-br. Becomes the URL prefix (/fr/...).'),
            TextInput::make('name')->required(),
            TextInput::make('native_name')->required(),
            Select::make('direction')->options(['ltr' => 'Left to right', 'rtl' => 'Right to left'])->default('ltr'),
            Toggle::make('is_enabled')->label('Enabled on the website')->inline(false)->helperText('Pages without an approved translation fall back to the default language.'),
            Toggle::make('is_default')->label('Default language')->inline(false),
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
                TextColumn::make('native_name'),
                TextColumn::make('code')->badge(),
                ToggleColumn::make('is_enabled')->label('Enabled')->disabled(fn (Language $r) => $r->is_default),
                IconColumn::make('is_default')->label('Default')->boolean(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLanguages::route('/'),
            'create' => Pages\CreateLanguage::route('/create'),
            'edit' => Pages\EditLanguage::route('/{record}/edit'),
        ];
    }
}
