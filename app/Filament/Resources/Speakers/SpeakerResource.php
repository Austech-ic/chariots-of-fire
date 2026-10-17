<?php

namespace App\Filament\Resources\Speakers;

use App\Filament\Resources\BaseResource;
use App\Filament\Support\Fields;
use App\Models\Speaker;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class SpeakerResource extends BaseResource
{
    protected static ?string $model = Speaker::class;

    protected static string $permission = 'sermons.manage';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Watch & Listen';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(120),
            Fields::slug(),
            Fields::media('portrait_asset_id', 'Portrait'),
            Select::make('status')->options(['active' => 'Active', 'archived' => 'Archived'])->default('active'),
            Textarea::make('biography')->rows(4)->columnSpanFull()->helperText('Only ministry-approved biographical details.'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('sermons'))
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('sermons_count')->label('Sermons'),
                TextColumn::make('status')->badge(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSpeakers::route('/'),
            'create' => Pages\CreateSpeaker::route('/create'),
            'edit' => Pages\EditSpeaker::route('/{record}/edit'),
        ];
    }
}
