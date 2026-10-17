<?php

namespace App\Filament\Resources\NewsletterSubscriptions;

use App\Filament\Resources\BaseResource;
use App\Jobs\SyncNewsletterSubscription;
use App\Models\NewsletterSubscription;
use App\Support\Audit;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/** Consent records. Subscribers opt in themselves; staff can only suppress or export. */
class NewsletterSubscriptionResource extends BaseResource
{
    protected static ?string $model = NewsletterSubscription::class;

    protected static string $permission = 'newsletter.manage';

    protected static array $deniedActions = ['create', 'update', 'delete', 'deleteAny'];

    protected static ?string $navigationLabel = 'Newsletter subscribers';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|UnitEnum|null $navigationGroup = 'Community';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('consents'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('email_normalized')->label('Email')->searchable()->copyable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('status')->badge()->formatStateUsing(fn ($state) => NewsletterSubscription::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) { 'active' => 'success', 'pending' => 'warning', default => 'gray' }),
                TextColumn::make('locale'),
                TextColumn::make('subscribed_at')->dateTime('j M Y', config('app.display_timezone'))->placeholder('—')->sortable(),
                TextColumn::make('provider_sync_status')->label('Sync')->badge()->tooltip(fn ($record) => $record->provider_sync_error)
                    ->color(fn ($state) => match ($state) { 'synced' => 'success', 'error' => 'danger', default => 'gray' }),
            ])
            ->filters([SelectFilter::make('status')->options(NewsletterSubscription::STATUSES)])
            ->headerActions([
                Action::make('export')->label('Export active subscribers')->icon(Heroicon::OutlinedArrowDownTray)
                    ->action(function () {
                        Audit::log('newsletter_exported');

                        return response()->streamDownload(function () {
                            $out = fopen('php://output', 'w');
                            fputcsv($out, ['email', 'name', 'locale', 'subscribed_at']);
                            NewsletterSubscription::where('status', 'active')->chunk(500, function ($rows) use ($out) {
                                foreach ($rows as $s) {
                                    fputcsv($out, [$s->email_normalized, $s->name, $s->locale, $s->subscribed_at?->toIso8601String()]);
                                }
                            });
                            fclose($out);
                        }, 'newsletter-active-'.now()->format('Ymd').'.csv');
                    }),
            ])
            ->recordActions([
                Action::make('suppress')->icon(Heroicon::OutlinedNoSymbol)->color('danger')->requiresConfirmation()
                    ->modalDescription('The address will never receive marketing email again, even if re-submitted.')
                    ->visible(fn (NewsletterSubscription $r) => $r->status !== 'suppressed')
                    ->action(function (NewsletterSubscription $r) {
                        $r->update(['status' => 'suppressed', 'unsubscribed_at' => now()]);
                        $r->consents()->whereNull('withdrawn_at')->update(['withdrawn_at' => now()]);
                        Audit::log('suppressed', $r);
                        SyncNewsletterSubscription::dispatch($r->id);
                    }),
                Action::make('resync')->label('Retry sync')->icon(Heroicon::OutlinedArrowPath)
                    ->visible(fn (NewsletterSubscription $r) => $r->provider_sync_status === 'error')
                    ->action(fn (NewsletterSubscription $r) => SyncNewsletterSubscription::dispatch($r->id)),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListNewsletterSubscriptions::route('/')];
    }
}
