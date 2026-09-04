<?php

namespace App\Filament\Merchant\Resources\StockMovements\Tables;

use App\Enums\Inventories\StockMovementType;
use App\Filament\Exports\StockMovementExporter;
use App\Filament\Merchant\Resources\StockMovements\StockMovementResource;
use App\Models\Inventories\StockMovement;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class StockMovementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (StockMovement $record): string => StockMovementResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex()
                    ->width(10)
                    ->alignCenter()
                    ->visibleFrom('md'),
                TextColumn::make('created_at')
                    ->label('Tanggal Kejadian')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('item.name')
                    ->label('Item')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label('Jumlah')
                    ->formatStateUsing(fn ($state): string => ($state > 0 ? '+' : '').format_quantity($state))
                    ->suffix(fn (StockMovement $record): string => ' '.$record->item?->unit)
                    ->color(fn ($state): string => $state > 0 ? 'success' : 'danger')
                    ->sortable(),
                TextColumn::make('quantity_before')
                    ->label('Sebelum')
                    ->formatStateUsing(fn ($state): string => format_quantity($state))
                    ->suffix(fn (StockMovement $record): string => ' '.$record->item?->unit)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('quantity_after')
                    ->label('Sesudah')
                    ->formatStateUsing(fn ($state): string => format_quantity($state))
                    ->suffix(fn (StockMovement $record): string => ' '.$record->item?->unit)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reference_type')
                    ->label('Sumber')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('creator.name')
                    ->label('Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                DateRangeFilter::make('created_at')
                    ->label('Periode')
                    ->placeholder('Pilih periode'),
                SelectFilter::make('type')
                    ->label('Tipe')
                    ->options(StockMovementType::class)
                    ->placeholder('Pilih tipe'),
                SelectFilter::make('item_id')
                    ->label('Item')
                    ->relationship('item', 'name')
                    ->placeholder('Pilih item'),

            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(StockMovementExporter::class)
                    ->columnMapping(false),
            ])
            ->bulkActions([
                ExportBulkAction::make()
                    ->exporter(StockMovementExporter::class)
                    ->columnMapping(false),
            ]);
    }
}
