<?php

namespace App\Filament\Merchant\Resources\GoodsReceipts\Tables;

use App\Enums\Inventories\GoodsReceiptStatus;
use App\Enums\Inventories\ReceiptSourceType;
use App\Filament\Exports\GoodsReceiptExporter;
use App\Filament\Merchant\Resources\GoodsReceipts\Actions\VerifyReceiptAction;
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

class GoodsReceiptsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex()
                    ->width(10)
                    ->alignCenter()
                    ->visibleFrom('md'),
                TextColumn::make('receipt_number')
                    ->label('No. Penerimaan')
                    ->description(fn ($record) => $record->verified_at?->format('d M Y') ?? '-')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('purchaseOrder.po_number')
                    ->label('No. PO')
                    ->description(fn ($record) => $record->purchaseOrder?->created_at?->format('d M Y'))
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('source_type')
                    ->label('Sumber')
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->tooltip('Stok akan bertambah/berkurang ketika status=Selesai')
                    ->sortable(),
                TextColumn::make('items_count')
                    ->label('Item')
                    ->counts('items'),
                TextColumn::make('verified_at')
                    ->label('Diverifikasi')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->orderByRaw("CASE WHEN status = 'draft' THEN 0 ELSE 1 END")->orderByDesc('id'))
            ->filters([
                SelectFilter::make('source_type')
                    ->label('Sumber')
                    ->options(ReceiptSourceType::class)
                    ->placeholder('Pilih sumber'),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(GoodsReceiptStatus::class)
                    ->placeholder('Pilih status'),
                DateRangeFilter::make('created_at')
                    ->label('Periode')
                    ->placeholder('Pilih rentang tanggal'),
                TrashedFilter::make(),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->visible(fn ($record) => $record->status === GoodsReceiptStatus::Draft),
                    DeleteAction::make()
                        ->visible(fn ($record) => $record->status === GoodsReceiptStatus::Draft),
                    // ForceDeleteAction::make(),
                    // RestoreAction::make(),
                    VerifyReceiptAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(GoodsReceiptExporter::class)
                        ->columnMapping(false),
                    // DeleteBulkAction::make(),
                    // ForceDeleteBulkAction::make(),
                    // RestoreBulkAction::make(),
                ]),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(GoodsReceiptExporter::class)
                    ->columnMapping(false),
            ]);
    }
}
