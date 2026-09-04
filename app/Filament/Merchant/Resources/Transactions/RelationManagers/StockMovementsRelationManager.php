<?php

namespace App\Filament\Merchant\Resources\Transactions\RelationManagers;

use App\Enums\Inventories\StockMovementType;
use App\Models\Inventories\StockMovement;
use App\Models\Transactions\Transaction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StockMovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'stockMovements';

    protected static ?string $recordTitleAttribute = 'id';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Mutasi Stok';
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Mutasi Stok')
            ->description('Riwayat mutasi stok yang terjadi akibat transaksi ini')
            ->modifyQueryUsing(
                fn (Builder $query) => $query
                    ->where('reference_type', Transaction::class)
                    ->where('reference_id', $this->getOwnerRecord()->getKey())
                    ->with(['item', 'creator'])
            )
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex()
                    ->alignCenter()
                    ->width(10),
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (StockMovementType $state): string => $state->getColor())
                    ->icon(fn (StockMovementType $state): string => $state->getIcon())
                    ->formatStateUsing(fn (StockMovementType $state): string => $state->getLabel())
                    ->sortable(),
                TextColumn::make('item.name')
                    ->label('Item')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('quantity_before')
                    ->label('Stok Sebelum')
                    ->sortable()
                    ->alignRight()
                    ->formatStateUsing(fn ($state): string => format_quantity($state))
                    ->suffix(fn (StockMovement $record): string => ' '.$record->item?->unit),
                TextColumn::make('quantity')
                    ->label('Qty')
                    ->sortable()
                    ->color(fn ($state): string => $state > 0 ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state): string => ($state > 0 ? '+' : '').format_quantity($state))
                    ->suffix(fn (StockMovement $record): string => ' '.$record->item?->unit),
                TextColumn::make('quantity_after')
                    ->label('Stok Sesudah')
                    ->sortable()
                    ->alignRight()
                    ->formatStateUsing(fn ($state): string => format_quantity($state))
                    ->suffix(fn (StockMovement $record): string => ' '.$record->item?->unit),
                TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Jenis Mutasi')
                    ->options(StockMovementType::class)
                    ->placeholder('Pilih jenis mutasi'),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
