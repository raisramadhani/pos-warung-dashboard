<?php

namespace App\Filament\Merchant\Resources\CashFlows\Tables;

use App\Enums\CashFlows\CashFlowType;
use App\Filament\Exports\CashFlowExporter;
use App\Filament\Merchant\Resources\CashFlows\Actions\EditCashFlowAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class CashFlowsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('transaction_date', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex()
                    ->sortable()
                    ->width(10)
                    ->alignCenter()
                    ->visibleFrom('md'),
                TextColumn::make('transaction_date')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Keterangan')
                    ->placeholder('-')
                    ->searchable()
                    ->limit(30),
                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Jumlah')
                    ->numeric()
                    ->color(fn (int $state): string => $state < 0 ? 'danger' : 'success')
                    ->sortable()
                    ->summarize(
                        Sum::make()
                            ->numeric()
                            ->label('Grand Total')
                    ),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Jenis')
                    ->options(CashFlowType::class)
                    ->placeholder('Pilih jenis'),
                DateRangeFilter::make('transaction_date')
                    ->label('Periode Keuangan')
                    ->placeholder('Pilih rentang tanggal'),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditCashFlowAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(CashFlowExporter::class)
                    ->columnMapping(false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(CashFlowExporter::class)
                        ->columnMapping(false),
                ]),
            ]);
    }
}
