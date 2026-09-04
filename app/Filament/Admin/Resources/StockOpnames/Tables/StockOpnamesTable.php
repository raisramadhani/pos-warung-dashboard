<?php

namespace App\Filament\Admin\Resources\StockOpnames\Tables;

use App\Enums\Inventories\StockOpnameStatus;
use App\Filament\Exports\StockOpnameExporter;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class StockOpnamesTable
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
                TextColumn::make('created_at')
                    ->label('Tanggal Opname')
                    ->date('d F Y')
                    ->description(fn ($record) => $record->created_at?->format('H:i').' WIB')
                    ->sortable(),
                TextColumn::make('merchant.name')
                    ->label('Outlet')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('opname_number')
                    ->label('No. Opname')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('items_count')
                    ->label('Item Tersedia')
                    ->counts('items')
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('counted_items_count')
                    ->label('Item Terverifikasi')
                    ->counts('countedItems')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('total_surplus')
                    ->label('Kelebihan')
                    ->color('warning')
                    ->sortable()
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => format_quantity($state))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total_deficit')
                    ->label('Kekurangan')
                    ->color('danger')
                    ->sortable()
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => format_quantity($state))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total_difference')
                    ->label('Selisih Bersih')
                    ->color(fn (mixed $state): string => match (true) {
                        (int) ($state ?? 0) < 0 => 'danger',
                        (int) ($state ?? 0) > 0 => 'warning',
                        default => 'success',
                    })
                    ->alignCenter()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => format_quantity($state))
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_lock_transactions')
                    ->label('Lock')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedLockClosed)
                    ->falseIcon(Heroicon::OutlinedLockOpen)
                    ->color(fn (bool $state): string => $state ? 'danger' : 'success'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->label('Tanggal Selesai')
                    ->date('d F Y')
                    ->description(fn ($record) => $record->completed_at?->format('H:i').' WIB')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                DateRangeFilter::make('created_at')
                    ->label('Periode Opname')
                    ->placeholder('Pilih rentang tanggal'),
                SelectFilter::make('merchant_id')
                    ->label('Outlet')
                    ->relationship('merchant', 'name', fn ($query) => $query->merchantsOnly())
                    ->searchable()
                    ->preload()
                    ->placeholder('Pilih outlet'),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(StockOpnameStatus::class)
                    ->placeholder('Pilih status'),
                SelectFilter::make('is_lock_transactions')
                    ->label('Lock Transaksi')
                    ->options([
                        '1' => 'Ya',
                        '0' => 'Tidak',
                    ])
                    ->placeholder('Pilih lock transaksi'),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(StockOpnameExporter::class)
                    ->columnMapping(false),
            ])
            ->bulkActions([
                ExportBulkAction::make()
                    ->exporter(StockOpnameExporter::class)
                    ->columnMapping(false),
            ]);
    }
}
