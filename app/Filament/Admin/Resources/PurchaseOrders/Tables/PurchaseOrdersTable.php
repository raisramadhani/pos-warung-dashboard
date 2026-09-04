<?php

namespace App\Filament\Admin\Resources\PurchaseOrders\Tables;

use App\Enums\Inventories\PurchaseOrderSource;
use App\Enums\Inventories\PurchaseOrderStatus;
use App\Filament\Admin\Resources\PurchaseOrders\Actions\ClosePurchaseOrderAction;
use App\Filament\Admin\Resources\PurchaseOrders\Actions\ReceiveGoodsAction;
use App\Filament\Exports\PurchaseOrderExporter;
use App\Models\Merchants\Merchant;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class PurchaseOrdersTable
{
    public static function configure(Table $table): Table
    {
        // Resolve id gudang default (merchant pertama bertipe warehouse) sekali
        // per request, lalu di-capture ke closure agar tidak di-query ulang.
        $warehouseId = Merchant::query()
            ->where('type', 'warehouse')
            ->first()?->id;

        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex()
                    ->width(10)
                    ->alignCenter()
                    ->visibleFrom('md'),
                TextColumn::make('po_number')
                    ->label('No. PO')
                    ->description(fn ($record) => $record->created_at?->format('d M Y'))
                    ->searchable()
                    ->sortable(),
                // TextColumn::make('merchant.name')
                //     ->label('Gudang')
                //     ->searchable()
                //     ->sortable()
                //     ->placeholder('-'),
                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                // TextColumn::make('source_type')
                //     ->label('Sumber')
                //     ->badge()
                //     ->sortable(),
                TextColumn::make('items_count')
                    ->label('Item')
                    ->counts('items'),
                TextColumn::make('status')
                    ->label('Status')
                    ->alignCenter()
                    ->badge()
                    ->description(fn ($record) => $record->status->getDescription())
                    ->tooltip('Stok akan bertambah/berkurang ketika status=Selesai')
                    ->sortable(),
                TextColumn::make('approved_at')
                    ->label('Disetujui')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('finished_at')
                    ->label('Selesai')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // SelectFilter::make('merchant_id')
                //     ->label('Gudang')
                //     ->relationship('merchant', 'name')
                //     ->searchable()
                //     ->preload()
                //     ->default($warehouseId)
                //     ->placeholder('Pilih gudang'),
                // SelectFilter::make('source_type')
                //     ->label('Sumber')
                //     ->options(PurchaseOrderSource::class)
                //     ->placeholder('Pilih sumber'),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(PurchaseOrderStatus::class)
                    ->placeholder('Pilih status'),
                SelectFilter::make('supplier_id')
                    ->label('Supplier')
                    ->relationship('supplier', 'name')
                    ->placeholder('Pilih supplier'),
                DateRangeFilter::make('created_at')
                    ->label('Periode')
                    ->placeholder('Pilih rentang tanggal'),
                // TrashedFilter::make(),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    ReceiveGoodsAction::make(),
                    ClosePurchaseOrderAction::make(),
                    EditAction::make()
                        ->visible(fn ($record) => $record->status === PurchaseOrderStatus::Approved),
                    // DeleteAction::make(),
                    // ForceDeleteAction::make(),
                    // RestoreAction::make(),
                ]),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(PurchaseOrderExporter::class)
                    ->columnMapping(false),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(PurchaseOrderExporter::class)
                        ->columnMapping(false),
                    // DeleteBulkAction::make(),
                    // ForceDeleteBulkAction::make(),
                    // RestoreBulkAction::make(),
                ]),
            ]);
    }
}
