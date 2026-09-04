<?php

namespace App\Filament\Admin\Resources\Transactions\Tables;

use App\Enums\Payments\PaymentMethod;
use App\Filament\Exports\TransactionExporter;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Query\Builder;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('transaction_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex()
                    ->sortable()
                    ->width(10)
                    ->alignCenter()
                    ->visibleFrom('md'),
                TextColumn::make('transaction_number')
                    ->label('No. Transaksi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('transaction_at')
                    ->label('Tanggal')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('merchant.name')
                    ->label('Outlet')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->label('Metode Bayar')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('items_count')
                    ->label('Item')
                    ->sortable()
                    ->summarize(
                        Sum::make()
                            ->label('Total Item')
                            ->using(
                                fn (Builder $query): int => (int) $query->sum('items_count')
                            )
                    ),
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->numeric()
                    ->sortable()
                    ->summarize(
                        Sum::make()
                            ->numeric()
                            ->label('Grand Total')
                    ),
                TextColumn::make('amount_received')
                    ->label('Uang Diterima')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('change')
                    ->label('Kembalian')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('merchant_id')
                    ->label('Outlet')
                    ->relationship('merchant', 'name', fn ($query) => $query->merchantsOnly())
                    ->searchable()
                    ->preload()
                    ->placeholder('Pilih outlet'),
                DateRangeFilter::make('transaction_at')
                    ->label('Periode Transaksi')
                    ->placeholder('Pilih rentang tanggal'),
                SelectFilter::make('payment_method')
                    ->label('Metode Bayar')
                    ->options(PaymentMethod::class)
                    ->placeholder('Pilih metode bayar'),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(TransactionExporter::class)
                    ->columnMapping(false),
            ])
            ->bulkActions([
                ExportBulkAction::make()
                    ->exporter(TransactionExporter::class)
                    ->columnMapping(false),
            ]);
    }
}
