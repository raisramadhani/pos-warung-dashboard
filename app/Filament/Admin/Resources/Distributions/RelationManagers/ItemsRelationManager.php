<?php

namespace App\Filament\Admin\Resources\Distributions\RelationManagers;

use App\Filament\Exports\DistributionItemExporter;
use App\Models\Inventories\DistributionItem;
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

    public function table(Table $table): Table
    {
        return $table
            ->heading('Item Barang')
            ->description('Daftar item dalam distribusi ini')
            ->modifyQueryUsing(fn ($query) => $query->with('item'))
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex()
                    ->alignCenter()
                    ->width(10),
                TextColumn::make('item.name')
                    ->label('Item')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('item.type')
                    ->label('Tipe')
                    ->badge(),
                TextColumn::make('quantity_sent')
                    ->label('Dikirim')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => format_quantity($state))
                    ->suffix(fn (TextColumn $column, ?DistributionItem $record): string => ' '.$record?->item?->unit),
                TextColumn::make('quantity_received')
                    ->label('Diterima')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => format_quantity($state))
                    ->suffix(fn (TextColumn $column, ?DistributionItem $record): string => ' '.$record?->item?->unit),
            ])
            ->filters([])
            ->headerActions([
                ExportAction::make()
                    ->exporter(DistributionItemExporter::class)
                    ->columnMapping(false),
            ])
            ->actions([])
            ->bulkActions([
                ExportBulkAction::make()
                    ->exporter(DistributionItemExporter::class)
                    ->columnMapping(false),
            ]);
    }
}
