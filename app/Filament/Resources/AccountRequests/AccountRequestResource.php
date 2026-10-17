<?php

namespace App\Filament\Resources\AccountRequests;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\Members\MemberAccountResource;
use App\Models\AccountRequest;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class AccountRequestResource extends BaseResource
{
    protected static ?string $model = AccountRequest::class;

    protected static string $permission = 'members.manage';

    protected static array $deniedActions = ['create', 'update', 'delete', 'deleteAny'];

    protected static ?string $navigationLabel = 'Account requests';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldExclamation;

    protected static string|UnitEnum|null $navigationGroup = 'Community';

    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        $n = AccountRequest::where('status', 'open')->count();

        return $n ? (string) $n : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('user'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->since(),
                TextColumn::make('user.email')->label('Member')->searchable(),
                TextColumn::make('type')->formatStateUsing(fn ($state) => AccountRequest::TYPES[$state] ?? $state),
                TextColumn::make('details')->limit(60)->tooltip(fn ($record) => $record->details),
                TextColumn::make('status')->badge(),
            ])
            ->filters([SelectFilter::make('status')->options(['open' => 'Open', 'completed' => 'Completed', 'declined' => 'Declined'])])
            ->recordActions([
                Action::make('deactivate')->label('Deactivate account')->color('danger')->requiresConfirmation()
                    ->visible(fn (AccountRequest $r) => $r->status === 'open' && in_array($r->type, ['deactivation', 'deletion'], true) && $r->user?->isActive())
                    ->action(fn (AccountRequest $r) => MemberAccountResource::setStatus($r->user, User::STATUS_DEACTIVATED)),
                Action::make('complete')->label('Mark completed')->color('success')->requiresConfirmation()
                    ->visible(fn (AccountRequest $r) => $r->status === 'open')
                    ->action(fn (AccountRequest $r) => $r->update(['status' => 'completed', 'handled_by' => auth()->id(), 'handled_at' => now()])),
                Action::make('decline')->color('gray')->requiresConfirmation()
                    ->visible(fn (AccountRequest $r) => $r->status === 'open')
                    ->action(fn (AccountRequest $r) => $r->update(['status' => 'declined', 'handled_by' => auth()->id(), 'handled_at' => now()])),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListAccountRequests::route('/')];
    }
}
