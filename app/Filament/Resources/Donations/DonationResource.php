<?php

namespace App\Filament\Resources\Donations;

use App\Filament\Resources\BaseResource;
use App\Models\Donation;
use App\Models\DonationCampaign;
use App\Services\Payments\DonationService;
use App\Services\Payments\PaymentException;
use App\Support\Audit;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Forms\Components\DatePicker;
use UnitEnum;

/** Read-only financial records; state changes come only from verified provider data. */
class DonationResource extends BaseResource
{
    protected static ?string $model = Donation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Giving';

    protected static ?int $navigationSort = 1;

    public static function infolist(Schema $schema): Schema
    {
        $tz = config('app.display_timezone');

        return $schema->components([
            Section::make('Donation')->schema([
                TextEntry::make('reference')->fontFamily('mono')->copyable(),
                TextEntry::make('amount')->state(fn (Donation $r) => $r->formattedAmount()),
                TextEntry::make('status')->badge(),
                TextEntry::make('campaign.name')->label('Purpose')->placeholder('General'),
                TextEntry::make('provider'),
                TextEntry::make('provider_transaction_id')->label('Provider transaction')->placeholder('—')->copyable(),
                TextEntry::make('created_at')->dateTime('j M Y H:i', $tz),
                TextEntry::make('paid_at')->dateTime('j M Y H:i', $tz)->placeholder('—'),
                TextEntry::make('reconciliation_status')->badge(),
            ])->columns(3),
            Section::make('Donor')->schema([
                TextEntry::make('donor_name')->placeholder('—'),
                TextEntry::make('donor_email')->placeholder('—'),
                TextEntry::make('is_anonymous')->label('Anonymous')->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
                TextEntry::make('user.email')->label('Member account')->placeholder('—'),
                TextEntry::make('note')->placeholder('—')->columnSpanFull(),
            ])->columns(4),
            Section::make('Payment attempts')->schema([
                RepeatableEntry::make('paymentAttempts')->hiddenLabel()->schema([
                    TextEntry::make('idempotency_key')->label('Reference')->fontFamily('mono'),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('source'),
                    TextEntry::make('provider_status')->placeholder('—'),
                    TextEntry::make('failure_reason')->placeholder('—'),
                    TextEntry::make('verified_at')->dateTime('j M Y H:i', $tz)->placeholder('—'),
                ])->columns(6),
            ])->collapsible(),
            Section::make('Refunds')->schema([
                RepeatableEntry::make('refunds')->hiddenLabel()->schema([
                    TextEntry::make('amount_minor')->label('Amount')->formatStateUsing(fn ($state, $record) => Money::format($state, $record->donation->currency)),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('reason'),
                    TextEntry::make('requester.name')->label('By'),
                    TextEntry::make('created_at')->dateTime('j M Y H:i', $tz),
                ])->columns(5),
            ])->visible(fn (Donation $r) => $r->refunds()->exists()),
        ]);
    }

    public static function table(Table $table): Table
    {
        $tz = config('app.display_timezone');

        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('campaign'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference')->fontFamily('mono')->searchable()->copyable(),
                TextColumn::make('amount_minor')->label('Amount')->formatStateUsing(fn ($state, Donation $r) => $r->formattedAmount())->sortable(),
                TextColumn::make('campaign.name')->label('Purpose')->placeholder('General'),
                TextColumn::make('donor')->state(fn (Donation $r) => $r->is_anonymous ? 'Anonymous' : ($r->donor_name ?: $r->donor_email))->searchable(query: fn ($query, string $search) => $query->where(fn ($w) => $w->where('donor_name', 'like', "%{$search}%")->orWhere('donor_email', 'like', "%{$search}%"))),
                TextColumn::make('status')->badge()->color(fn ($state) => match ($state) { 'paid' => 'success', 'pending' => 'warning', 'failed', 'reversed' => 'danger', default => 'gray' }),
                TextColumn::make('reconciliation_status')->label('Reconciled')->badge()->color(fn ($state) => match ($state) { 'matched' => 'success', 'mismatch' => 'danger', default => 'gray' }),
                TextColumn::make('provider')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->dateTime('j M Y H:i', $tz)->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(Donation::STATUSES)->multiple(),
                SelectFilter::make('campaign_id')->label('Purpose')->options(fn () => DonationCampaign::pluck('name', 'id')),
                SelectFilter::make('reconciliation_status')->options(['unreconciled' => 'Unreconciled', 'matched' => 'Matched', 'mismatch' => 'Mismatch']),
                Filter::make('period')->schema([DatePicker::make('from'), DatePicker::make('until')])
                    ->query(fn ($query, array $data) => $query
                        ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                        ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))),
            ])
            ->headerActions([
                Action::make('export')->label('Export CSV')->icon(Heroicon::OutlinedArrowDownTray)
                    ->visible(fn () => auth()->user()->can('export', Donation::class))
                    ->action(function ($livewire) {
                        $query = $livewire->getFilteredTableQuery()->with('campaign');
                        Audit::log('donations_exported');

                        return response()->streamDownload(function () use ($query) {
                            $out = fopen('php://output', 'w');
                            fputcsv($out, ['Reference', 'Created', 'Paid at', 'Status', 'Currency', 'Amount', 'Purpose', 'Donor name', 'Donor email', 'Anonymous', 'Provider', 'Provider transaction', 'Reconciliation']);
                            $query->chunk(500, function ($rows) use ($out) {
                                foreach ($rows as $d) {
                                    fputcsv($out, [
                                        $d->reference, local_time($d->created_at, 'Y-m-d H:i'), local_time($d->paid_at, 'Y-m-d H:i'), $d->status, $d->currency,
                                        number_format($d->amount_minor / (10 ** Money::exponent($d->currency)), Money::exponent($d->currency), '.', ''),
                                        $d->campaign?->name, $d->is_anonymous ? '' : $d->donor_name, $d->donor_email, $d->is_anonymous ? 'yes' : 'no',
                                        $d->provider, $d->provider_transaction_id, $d->reconciliation_status,
                                    ]);
                                }
                            });
                            fclose($out);
                        }, 'donations-'.now()->format('Ymd-His').'.csv');
                    }),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function recordActions(): array
    {
        return [
            Action::make('verify')->label('Verify with provider')->icon(Heroicon::OutlinedArrowPath)
                ->visible(fn (Donation $record) => in_array($record->status, ['pending', 'failed', 'abandoned'], true))
                ->action(function (Donation $record) {
                    $fresh = app(DonationService::class)->confirm($record, 'verification');
                    Notification::make()->title('Status: '.$fresh->status)->send();
                }),
            Action::make('refund')->icon(Heroicon::OutlinedReceiptRefund)->color('danger')
                ->visible(fn (Donation $record) => in_array($record->status, ['paid', 'partially_refunded'], true) && auth()->user()->can('refund', $record))
                ->schema(fn (Donation $record) => [
                    TextInput::make('amount')->label('Amount ('.$record->currency.')')->required()->regex('/^\d+(\.\d{1,2})?$/')
                        ->default(number_format(($record->amount_minor - $record->refundedMinor()) / (10 ** Money::exponent($record->currency)), Money::exponent($record->currency), '.', '')),
                    Textarea::make('reason')->required()->rows(2),
                ])
                ->requiresConfirmation()
                ->action(function (Donation $record, array $data) {
                    try {
                        $refund = app(DonationService::class)->refund($record, Money::toMinor($data['amount'], $record->currency), $data['reason'], auth()->user());
                        Notification::make()->{$refund->status === 'processed' ? 'success' : 'danger'}()->title('Refund '.$refund->status)->send();
                    } catch (PaymentException $e) {
                        Notification::make()->danger()->title($e->getMessage())->send();
                    }
                }),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDonations::route('/'),
            'view' => Pages\ViewDonation::route('/{record}'),
        ];
    }
}
