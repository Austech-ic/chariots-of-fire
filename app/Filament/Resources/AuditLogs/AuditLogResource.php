<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\BaseResource;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class AuditLogResource extends BaseResource
{
    protected static ?string $model = AuditLog::class;

    protected static string $permission = 'audit.view';

    protected static array $deniedActions = ['create', 'update', 'delete', 'deleteAny'];

    protected static ?string $navigationLabel = 'Audit log';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 8;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('actor'))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')->dateTime('j M Y H:i:s', config('app.display_timezone'))->sortable(),
                TextColumn::make('actor.name')->label('Who')->placeholder('System'),
                TextColumn::make('action')->searchable()->badge()->color('gray'),
                TextColumn::make('subject')->state(fn (AuditLog $r) => $r->subject_type ? class_basename($r->subject_type).' #'.$r->subject_id : '—'),
                TextColumn::make('metadata')->state(fn (AuditLog $r) => $r->metadata ? json_encode($r->metadata) : null)->limit(60)->placeholder('—'),
                TextColumn::make('ip_address')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('actor_user_id')->label('Who')->relationship('actor', 'name')->searchable(),
                Filter::make('action')->schema([TextInput::make('contains')])->query(fn ($query, array $data) => $query->when($data['contains'] ?? null, fn ($q, $v) => $q->where('action', 'like', "%{$v}%"))),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListAuditLogs::route('/')];
    }
}
