<?php

namespace App\Filament\Admin\Resources\Items\RelationManagers;

use App\Enums\Inventories\DistributionStatus;
use App\Models\Inventories\DistributionItem;
use BackedEnum;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class DistributionItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'distributionItems';

    protected static ?string $recordTitleAttribute = 'id';

    protected static string|BackedEnum|null $icon = Heroicon::Truck;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Distribusi';
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Riwayat Distribusi')
            ->description('Riwayat distribusi barang ini ke outlet')
            ->modifyQueryUsing(fn ($query) => $query->with(['item', 'distribution.merchant']))
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex()
                    ->alignCenter()
                    ->width(10),
                // TextColumn::make('distribution.id')
                //     ->label('Distribusi')
                //     ->sortable(),
                TextColumn::make('distribution.sent_at')
                    ->label('Tanggal Kirim')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('distribution.merchant.name')
                    ->label('Outlet')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('quantity_sent')
                    ->label('Dikirim')
                    ->sortable()
                    ->suffix(fn (?DistributionItem $record): ?string => $record?->item?->unit ? ' '.$record->item->unit : null),

                TextColumn::make('quantity_received')
                    ->label('Diterima')
                    ->sortable()
                    ->suffix(fn (?DistributionItem $record): ?string => $record?->item?->unit ? ' '.$record->item->unit : null),
                TextColumn::make('distribution.status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('distribution.received_at')
                    ->label('Tanggal Terima')
                    ->dateTime()
                    ->sortable(),

            ])
            ->defaultSort('distribution.sent_at', 'desc')
            ->filters([
                SelectFilter::make('distribution.status')
                    ->label('Status')
                    ->options(DistributionStatus::class)
                    ->placeholder('Pilih status'),
                SelectFilter::make('distribution.merchant')
                    ->label('Outlet')
                    ->relationship('distribution.merchant', 'name')
                    ->placeholder('Pilih outlet'),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
