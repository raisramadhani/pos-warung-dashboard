<?php

namespace App\Filament\Admin\Resources\Assets\Tables;

use App\Enums\Inventories\AssetStatus;
use App\Enums\Inventories\DepreciationMethod;
use App\Filament\Exports\AssetExporter;
use App\Models\Inventories\Asset;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

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
                TextColumn::make('useful_life_months')
                    ->label('Umur (bln)')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('annual_depreciation_rate')
                    ->label('Nilai / Thn')
                    ->suffix('%')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->state(fn (Asset $record): string => number_format($record->annual_depreciation_rate, 2, ',', '.')),
                TextColumn::make('monthly_depreciation')
                    ->label('Depresiasi/Bln')
                    ->numeric()
                    ->state(fn (Asset $record): float => $record->monthly_depreciation),
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
                DateRangeFilter::make('acquisition_date')
                    ->label('Periode Akuisisi')
                    ->placeholder('Pilih rentang tanggal'),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(AssetStatus::class)
                    ->placeholder('Pilih status'),

            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),

                ]),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(AssetExporter::class)
                    ->columnMapping(false),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(AssetExporter::class)
                        ->columnMapping(false),
                    // DeleteBulkAction::make(),

                ]),
            ]);
    }
}
