<?php

namespace App\Filament\Merchant\Resources\GoodsReceipts\RelationManagers;

use App\Filament\Exports\GoodsReceiptItemExporter;
use App\Filament\Merchant\Resources\GoodsReceipts\Actions\VerifyReceiptAction;
use App\Filament\Merchant\Resources\GoodsReceipts\Actions\VerifyReceiptItemAction;
use App\Models\Inventories\GoodsReceiptItem;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $recordTitleAttribute = 'item.name';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Item';
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Item Barang')
            ->description('Daftar item dalam penerimaan ini')
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex()
                    ->alignCenter()
                    ->width(10)
                    ->visibleFrom('md'),
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
                    ->suffix(fn (GoodsReceiptItem $record): string => ' '.$record->item?->unit),
                TextColumn::make('quantity_received')
                    ->label('Diterima')
                    ->sortable()
                    ->suffix(fn (GoodsReceiptItem $record): string => ' '.$record->item?->unit),
                TextColumn::make('unit_price')
                    ->label('Harga Satuan')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([])
            ->headerActions([
                VerifyReceiptAction::make(),
                ExportAction::make()
                    ->exporter(GoodsReceiptItemExporter::class)
                    ->columnMapping(false),
            ])
            ->actions([
                VerifyReceiptItemAction::make(),
            ])
            ->bulkActions([
                ExportBulkAction::make()
                    ->exporter(GoodsReceiptItemExporter::class)
                    ->columnMapping(false),
            ]);
    }
}
