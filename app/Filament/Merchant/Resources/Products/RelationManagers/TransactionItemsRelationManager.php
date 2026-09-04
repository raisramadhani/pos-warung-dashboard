<?php

namespace App\Filament\Merchant\Resources\Products\RelationManagers;

use App\Enums\Payments\PaymentMethod;
use App\Filament\Merchant\Resources\Transactions\TransactionResource;
use App\Models\Transactions\TransactionItem;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TransactionItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactionItems';

    protected static ?string $recordTitleAttribute = 'id';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Riwayat Transaksi';
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Riwayat Transaksi')
            ->description('Riwayat penjualan produk ini')
            ->recordUrl(function (TransactionItem $record) {
                return TransactionResource::getUrl('view', ['record' => $record->transaction]);
            })
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->sortable()
                    ->alignCenter()
                    ->width(10)
                    ->rowIndex()
                    ->visibleFrom('md'),
                TextColumn::make('transaction.transaction_number')
                    ->label('No. Transaksi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('transaction.payment_method')
                    ->label('Metode Bayar')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label('Qty')
                    ->sortable(),
                TextColumn::make('unit_price')
                    ->label('Harga Satuan')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('transaction.created_at')
                    ->label('Tanggal')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('payment_method')
                    ->label('Metode Bayar')
                    ->options(PaymentMethod::class)
                    ->placeholder('Pilih metode bayar')
                    ->query(
                        fn (Builder $query, array $data): Builder => $query->when(
                            filled($data['value']),
                            fn (Builder $query): Builder => $query->whereHas(
                                'transaction',
                                fn (Builder $query): Builder => $query->where('payment_method', $data['value']),
                            ),
                        ),
                    ),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }

    protected function getTableQuery(): Builder
    {
        $tenant = filament()->getTenant();

        return TransactionItem::whereHas('transaction', function (Builder $query) use ($tenant): void {
            $query->where('merchant_id', $tenant?->getKey());
        })->with(['transaction']);
    }
}
