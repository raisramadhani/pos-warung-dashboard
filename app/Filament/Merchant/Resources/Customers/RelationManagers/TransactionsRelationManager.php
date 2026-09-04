<?php

namespace App\Filament\Merchant\Resources\Customers\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected static ?string $recordTitleAttribute = 'transaction_number';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Riwayat Transaksi';
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Riwayat Transaksi')
            ->description('Daftar transaksi yang dilakukan oleh pelanggan ini')
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex()
                    ->width(10)
                    ->alignCenter(),
                TextColumn::make('transaction_number')
                    ->label('No. Transaksi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->label('Metode Bayar')
                    ->badge()
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('transaction_at')
                    ->label('Tanggal')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('transaction_at', 'desc')
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
