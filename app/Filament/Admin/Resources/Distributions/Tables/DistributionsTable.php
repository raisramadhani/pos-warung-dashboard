<?php

namespace App\Filament\Admin\Resources\Distributions\Tables;

use App\Enums\Inventories\DistributionStatus;
use App\Filament\Admin\Resources\Distributions\Actions\CloseDistributionAction;
use App\Filament\Exports\DistributionExporter;
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

class DistributionsTable
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
                TextColumn::make('sent_at')
                    ->label('Tanggal Kirim')
                    ->date('d F Y')
                    ->description(fn ($record) => $record->sent_at?->format('H:i').' WIB')
                    ->sortable(),
                TextColumn::make('sourceMerchant.name')
                    ->label('Asal')
                    ->alignCenter()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('merchant.name')
                    ->label('Tujuan')
                    ->alignCenter()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('items_count')
                    ->label('Item Dikirim')
                    ->alignCenter()
                    ->counts('items'),

                TextColumn::make('verified_items_count')
                    ->label('Item Diverifikasi')
                    ->alignCenter()
                    ->counts('verifiedItems'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->alignCenter()
                    ->description(fn ($record) => $record->status->getDescription())
                    ->tooltip('Stok akan bertambah/berkurang ketika status=Selesai')
                    ->sortable(),
                TextColumn::make('received_at')
                    ->label('Tanggal Diterima')
                    ->date('d F Y')
                    ->description(fn ($record) => $record->received_at?->format('H:i').' WIB')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                DateRangeFilter::make('sent_at')
                    ->label('Periode Pengiriman')
                    ->placeholder('Pilih rentang tanggal'),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(DistributionStatus::class)
                    ->placeholder('Pilih status'),
                SelectFilter::make('merchant_id')
                    ->label('Tujuan')
                    ->relationship('merchant', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Pilih tujuan'),
                SelectFilter::make('source_merchant_id')
                    ->label('Asal')
                    ->relationship('sourceMerchant', 'name')
                    ->searchable()
                    ->preload()
                    ->default($warehouseId)
                    ->placeholder('Pilih gudang'),
                // TrashedFilter::make(),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    CloseDistributionAction::make(),
                    // EditAction::make(),
                    // DeleteAction::make(),
                    // ForceDeleteAction::make(),
                    // RestoreAction::make(),
                ]),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(DistributionExporter::class)
                    ->columnMapping(false),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(DistributionExporter::class)
                        ->columnMapping(false),
                    // DeleteBulkAction::make(),
                    // ForceDeleteBulkAction::make(),
                    // RestoreBulkAction::make(),
                ]),
            ]);
    }
}
