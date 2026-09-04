<?php

namespace App\Filament\Admin\Resources\Merchants\RelationManagers;

use App\Enums\Inventories\ItemType;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class StocksRelationManager extends RelationManager
{
    protected static string $relationship = 'merchantStocks';

    protected static ?string $recordTitleAttribute = 'item.name';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Stok Outlet';
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Stok Outlet')
            ->description('Stok bahan baku dan alat di outlet')
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex()
                    ->width(10)
                    ->alignCenter(),
                TextColumn::make('item.name')
                    ->label('Item')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('item.type')
                    ->label('Tipe')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('item.unit')
                    ->label('Satuan'),
                TextColumn::make('quantity')
                    ->label('Stok')
                    ->sortable()
                    ->color(fn (mixed $state): string => match (true) {
                        (int) ($state ?? 0) <= 0 => 'danger',
                        (int) ($state ?? 0) <= 5 => 'warning',
                        default => 'success',
                    }),
            ])
            ->defaultSort('quantity', 'asc')
            ->filters([
                SelectFilter::make('item.type')
                    ->label('Tipe')
                    ->options(ItemType::class)
                    ->placeholder('Pilih tipe'),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
