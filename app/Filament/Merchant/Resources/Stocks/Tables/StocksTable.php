<?php

namespace App\Filament\Merchant\Resources\Stocks\Tables;

use App\Enums\Inventories\ItemType;
use App\Filament\Exports\MerchantStockExporter;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StocksTable
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
                TextColumn::make('item.name')
                    ->label('Item')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('item.type')
                    ->label('Tipe')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                // Kolom item.unit bisa dihapus jika tidak ingin ditampilkan dua kali,
                // atau dibiarkan jika masih butuh kolom terpisah.
                // TextColumn::make('item.unit')
                //     ->label('Satuan'),

                TextColumn::make('quantity')
                    ->label('Stok')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => format_quantity($state))
                    ->suffix(fn ($record) => ' '.$record->item?->unit) // <-- Tambahkan baris ini
                    ->color(fn (mixed $state): string => match (true) {
                        (int) ($state ?? 0) <= 0 => 'danger',
                        (int) ($state ?? 0) <= 5 => 'warning',
                        default => 'success',
                    }),
            ])
            ->filters([
                SelectFilter::make('item.type')
                    ->label('Tipe')
                    ->options(ItemType::class)
                    ->query(
                        fn (Builder $query, array $data): Builder => $query->when(
                            filled($data['value']),
                            fn (Builder $query): Builder => $query->whereHas(
                                'item',
                                fn (Builder $query): Builder => $query->where('type', $data['value']),
                            ),
                        ),
                    )
                    ->placeholder('Pilih tipe'),
            ])
            ->actions([
                // ViewAction::make(),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(MerchantStockExporter::class)
                    ->columnMapping(false),
            ])
            ->bulkActions([
                ExportBulkAction::make()
                    ->exporter(MerchantStockExporter::class)
                    ->columnMapping(false),
            ]);
    }
}
