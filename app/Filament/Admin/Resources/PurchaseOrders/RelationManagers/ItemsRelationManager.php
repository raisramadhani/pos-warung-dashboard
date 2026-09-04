<?php

namespace App\Filament\Admin\Resources\PurchaseOrders\RelationManagers;

use App\Filament\Exports\PurchaseOrderItemExporter;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $recordTitleAttribute = 'item.name';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Item';
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Item Barang')
            ->description('Daftar item dalam purchase order ini')
            ->modifyQueryUsing(fn (Builder $query) => $query->orderBy('id'))
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex()
                    ->alignCenter()
                    ->width(10),
                TextColumn::make('item.name')
                    ->label('Item')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('item.type')
                    ->label('Tipe')
                    ->badge(),
                TextColumn::make('quantity_ordered')
                    ->label('Dipesan')
                    ->sortable()
                    ->suffix(fn ($record): string => ' '.$record->item?->unit),
                TextColumn::make('quantity_received')
                    ->label('Diterima')
                    ->suffix(fn ($record): string => ' '.$record->item?->unit),
                TextColumn::make('unit_price_ordered')
                    ->label('Harga Satuan')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('subtotal_ordered')
                    ->label('Subtotal')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([])
            ->headerActions([
                ExportAction::make()
                    ->exporter(PurchaseOrderItemExporter::class)
                    ->columnMapping(false),
            ])
            ->actions([])
            ->bulkActions([
                ExportBulkAction::make()
                    ->exporter(PurchaseOrderItemExporter::class)
                    ->columnMapping(false),
            ]);
    }
}
