<?php

namespace App\Filament\Admin\Resources\Items\RelationManagers;

use App\Enums\Inventories\PurchaseOrderStatus;
use App\Models\Inventories\PurchaseOrderItem;
use BackedEnum;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'purchaseOrderItems';

    protected static ?string $recordTitleAttribute = 'id';

    protected static string|BackedEnum|null $icon = Heroicon::ClipboardDocumentList;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Purchase Order';
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Riwayat Purchase Order')
            ->description('Riwayat penerimaan barang ini dari supplier')
            ->modifyQueryUsing(fn ($query) => $query->with(['item', 'purchaseOrder']))
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex()
                    ->alignCenter()
                    ->width(10),
                TextColumn::make('purchaseOrder.po_number')
                    ->label('No. PO')
                    ->description(fn ($record) => $record->purchaseOrder?->created_at?->format('d M Y'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('purchaseOrder.supplier.name')
                    ->label('Supplier')
                    ->searchable(),
                TextColumn::make('quantity_ordered')
                    ->label('Dipesan')
                    ->sortable()
                    ->suffix(fn (?PurchaseOrderItem $record): ?string => $record?->item?->unit ? ' '.$record->item->unit : null),
                TextColumn::make('quantity_received')
                    ->label('Diterima')
                    ->suffix(fn (?PurchaseOrderItem $record): ?string => $record?->item?->unit ? ' '.$record->item->unit : null),
                TextColumn::make('unit_price_ordered')
                    ->label('Harga Satuan')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('subtotal_ordered')
                    ->label('Subtotal')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('purchaseOrder.status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('purchaseOrder.finished_at')
                    ->label('Tanggal Selesai')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('purchaseOrder.status')
                    ->label('Status PO')
                    ->options(PurchaseOrderStatus::class)
                    ->placeholder('Pilih status'),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
