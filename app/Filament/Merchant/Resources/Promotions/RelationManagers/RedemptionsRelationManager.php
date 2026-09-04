<?php

namespace App\Filament\Merchant\Resources\Promotions\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RedemptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'redemptions';

    protected static ?string $recordTitleAttribute = 'id';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Pemakaian';
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Riwayat Pemakaian Promo')
            ->description('Daftar transaksi yang memakai promo ini')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex()
                    ->alignCenter()
                    ->width(10),
                TextColumn::make('transaction.transaction_number')
                    ->label('No. Transaksi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('transaction.transaction_at')
                    ->label('Tanggal')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('Pelanggan')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('discount_amount')
                    ->label('Diskon')
                    ->numeric()
                    ->sortable()
                    ->alignRight(),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
