<?php

namespace App\Filament\Resources\MediaAssets;

use App\Filament\Resources\BaseResource;
use App\Filament\Support\Fields;
use App\Jobs\ProcessMediaAsset;
use App\Models\MediaAsset;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

class MediaAssetResource extends BaseResource
{
    protected static ?string $model = MediaAsset::class;

    protected static string $permission = 'media.manage';

    protected static ?string $navigationLabel = 'Media library';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('File')->schema([
                Select::make('upload_kind')->label('Type')->options(MediaAsset::KINDS)->default('image')->live()->dehydrated(false)
                    ->visibleOn('create'),
                Fields::upload('path', 'image')->label('Upload')->required()->visibleOn('create')
                    ->acceptedFileTypes(fn ($get) => config('fco.media.allowed.'.($get('upload_kind') ?: 'image').'.mimes'))
                    ->maxSize(fn ($get) => config('fco.media.allowed.'.($get('upload_kind') ?: 'image').'.max_kb'))
                    ->storeFileNamesIn('original_name'),
                TextInput::make('original_name')->disabled()->visibleOn('edit'),
                Select::make('visibility')->options(['private' => 'Private', 'public' => 'Public'])->default('private')
                    ->helperText('Media becomes public automatically when content using it is published.'),
            ])->columns(2),
            Section::make('Description')->schema([
                TextInput::make('alt_text')->label('Alternative text')->maxLength(255)->helperText('Describe the image for screen-reader users.'),
                TextInput::make('caption')->maxLength(255),
                TextInput::make('credit')->maxLength(255),
                TextInput::make('usage_permission')->label('Usage permission')->maxLength(255)->helperText('e.g. “Consent on file for all people shown”.'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('preview')->state(fn (MediaAsset $r) => $r->isImage() ? route('media.show', ['asset' => $r->uuid, 'v' => 'sm']) : null)->square()->imageSize(56),
                TextColumn::make('original_name')->label('File')->searchable()->limit(40)->description(fn (MediaAsset $r) => $r->alt_text),
                TextColumn::make('kind')->badge(),
                TextColumn::make('visibility')->badge()->color(fn ($state) => $state === 'public' ? 'success' : 'gray'),
                TextColumn::make('processing_status')->label('Processing')->badge()
                    ->color(fn ($state) => match ($state) { 'ready' => 'success', 'failed' => 'danger', default => 'warning' })
                    ->tooltip(fn (MediaAsset $r) => $r->processing_error),
                TextColumn::make('size_bytes')->label('Size')->formatStateUsing(fn ($state, MediaAsset $r) => $r->humanSize())->sortable(),
                TextColumn::make('created_at')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('kind')->options(MediaAsset::KINDS),
                SelectFilter::make('processing_status')->options(['pending' => 'Pending', 'processing' => 'Processing', 'ready' => 'Ready', 'failed' => 'Failed']),
            ])
            ->recordActions([
                Action::make('retry')->label('Retry processing')->icon(Heroicon::OutlinedArrowPath)
                    ->visible(fn (MediaAsset $r) => $r->processing_status === 'failed')
                    ->action(fn (MediaAsset $r) => ProcessMediaAsset::dispatch($r->id)),
                EditAction::make(),
                DeleteAction::make()->after(function (MediaAsset $r) {
                    $disk = Storage::disk($r->disk);
                    $disk->delete(array_merge([$r->path], array_column($r->variants ?? [], 'path')));
                }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMediaAssets::route('/'),
            'create' => Pages\CreateMediaAsset::route('/create'),
            'edit' => Pages\EditMediaAsset::route('/{record}/edit'),
        ];
    }
}
