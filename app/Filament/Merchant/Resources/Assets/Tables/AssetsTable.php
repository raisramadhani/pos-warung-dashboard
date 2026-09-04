<?php

namespace App\Filament\Merchant\Resources\Assets\Tables;

use App\Enums\Inventories\AssetStatus;
use App\Enums\Inventories\DepreciationMethod;
use App\Enums\Inventories\ItemType;
use App\Filament\Exports\AssetExporter;
use App\Filament\Merchant\Resources\Assets\AssetResource;
use App\Models\Inventories\Asset;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AssetsTable
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
                TextColumn::make('name')
                    ->label('Nama Aset')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('item.name')
                    ->label('Item')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('acquisition_date')
                    ->label('Tgl Akuisisi')
                    ->date()
                    ->sortable(),
                TextColumn::make('acquisition_cost')
                    ->label('Harga Akuisisi')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('depreciation_method')
                    ->label('Metode')
                    ->badge()
                    ->formatStateUsing(fn (DepreciationMethod $state): string => $state->getLabel())
                    ->sortable(),
                TextColumn::make('current_book_value')
                    ->label('Nilai Buku')
                    ->numeric()
                    ->state(fn (Asset $record): float => $record->current_book_value),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(AssetStatus::class)
                    ->placeholder('Pilih status'),
                SelectFilter::make('item.type')
                    ->label('Tipe Item')
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
                ActionGroup::make([
                    Action::make('view')
                        ->label('Lihat')
                        ->icon('heroicon-o-eye')
                        ->url(fn (Asset $record): string => AssetResource::getUrl('view', ['record' => $record])),
                ]),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(AssetExporter::class)
                    ->columnMapping(false),
            ])
            ->bulkActions([
                ExportBulkAction::make()
                    ->exporter(AssetExporter::class)
                    ->columnMapping(false),
            ]);
    }
}
