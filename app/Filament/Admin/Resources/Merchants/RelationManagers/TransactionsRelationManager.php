<?php

namespace App\Filament\Admin\Resources\Merchants\RelationManagers;

use App\Enums\Payments\PaymentMethod;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Transaksi';
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Transaksi')
            ->description('Riwayat transaksi penjualan outlet ini')
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex()
                    ->sortable()
                    ->width(10)
                    ->alignCenter(),
                TextColumn::make('transaction_number')
                    ->label('No. Transaksi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->label('Metode Bayar')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('items_count')
                    ->label('Item')
                    ->sortable(),
                TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('payment_method')
                    ->label('Metode Bayar')
                    ->options(PaymentMethod::class)
                    ->placeholder('Pilih metode bayar'),
                DateRangeFilter::make('created_at')
                    ->label('Periode')
                    ->placeholder('Pilih rentang tanggal'),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
