<?php

namespace App\Filament\Admin\Resources\Stocks\Tables;

use App\Enums\Inventories\ItemType;
use App\Filament\Admin\Resources\Stocks\StockResource;
use App\Filament\Exports\MerchantStockExporter;
use App\Models\Inventories\MerchantStock;
use App\Models\Merchants\Merchant;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StocksTable
{
    public static function configure(Table $table): Table
    {
        // Resolve id gudang default (merchant pertama bertipe warehouse) sekali
        // per request, lalu di-capture ke closure agar tidak di-query ulang.
        $warehouseId = Merchant::query()
            ->where('type', 'warehouse')
            ->first()?->id;

        return $table

            ->recordUrl(fn (MerchantStock $record): string => StockResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex()
                    ->width(10)
                    ->alignCenter()
                    ->visibleFrom('md'),
                TextColumn::make('merchant.name')
                    ->label('Lokasi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('item.name')
                    ->label('Item')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('item.type')
                    ->label('Tipe')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label('Stok')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => format_quantity($state))
                    ->suffix(fn ($record): string => ' '.$record->item?->unit)
                    ->color(fn (mixed $state): string => match (true) {
                        (int) ($state ?? 0) <= 0 => 'danger',
                        (int) ($state ?? 0) <= 10 => 'warning',
                        default => 'success',
                    }),
            ])
            ->filters([
                SelectFilter::make('merchant_id')
                    ->label('Lokasi')
                    ->relationship('merchant', 'name')
                    ->searchable()
                    ->preload()
                    ->default($warehouseId)
                    ->placeholder('Pilih lokasi'),
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
                SelectFilter::make('item_id')
                    ->label('Item')
                    ->relationship('item', 'name')
                    ->placeholder('Pilih item'),
                Filter::make('stok_menipis')
                    ->label('Stok Menipis')
                    ->query(fn (Builder $query) => $query->where('quantity', '<=', 10)),
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
