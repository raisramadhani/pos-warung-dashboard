<?php

namespace App\Filament\Merchant\Resources\Transactions\RelationManagers;

use App\Models\Transactions\TransactionItem;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\Summarizers\Sum;
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
        return 'Item Transaksi';
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Item Transaksi')
            ->description('Daftar item yang terjual dalam transaksi ini')
            ->modifyQueryUsing(
                fn (Builder $query) => $query->with(['product', 'promotion'])
            )
            ->defaultSort('id', 'asc')
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex()
                    ->alignCenter()
                    ->width(10),
                TextColumn::make('product_data.name')
                    ->label('Produk')
                    ->searchable()
                    ->sortable()
                    ->suffix(fn (TransactionItem $record): string => $record->unit_price === 0 ? ' (Gratis)' : ''),
                TextColumn::make('quantity')
                    ->label('Qty')
                    ->sortable()
                    ->alignCenter()
                    ->default(0)
                    ->summarize(
                        Sum::make()
                            ->label('Total Qty')
                    ),
                TextColumn::make('unit_price')
                    ->label('Harga Satuan')
                    ->numeric()
                    ->sortable()
                    ->default(0)
                    ->alignRight()
                    ->summarize(
                        Sum::make()
                            ->label('Total Harga Satuan')
                    ),
                TextColumn::make('original_price')
                    ->label('Harga Asli')
                    ->numeric()
                    ->sortable()
                    ->placeholder('-')
                    ->alignRight()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('discount_amount')
                    ->label('Diskon')
                    ->numeric()
                    ->sortable()
                    ->default(0)
                    ->alignRight()
                    ->summarize(
                        Sum::make()
                            ->label('Total Diskon')
                    ),
                TextColumn::make('promotion_data.name')
                    ->label('Promo')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subtotal')
                    ->label('Subtotal Harga')
                    ->numeric()
                    ->sortable()
                    ->default(0)
                    ->alignRight()
                    ->summarize(
                        Sum::make()
                            ->label('Total Harga')
                    ),
                TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('product')
                    ->label('Produk')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->placeholder('Pilih produk'),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
