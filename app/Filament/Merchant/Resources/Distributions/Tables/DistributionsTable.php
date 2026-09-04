<?php

namespace App\Filament\Merchant\Resources\Distributions\Tables;

use App\Enums\Inventories\DistributionStatus;
use App\Filament\Exports\DistributionExporter;
use App\Filament\Merchant\Resources\Distributions\Actions\FinishDistributionAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class DistributionsTable
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
                TextColumn::make('sent_at')
                    ->label('Tanggal Kirim')
                    ->date('d F Y')
                    ->description(fn ($record) => $record->sent_at?->format('H:i').' WIB')
                    ->sortable(),
                TextColumn::make('items_count')
                    ->label('Item Dikirim')
                    ->alignCenter()
                    ->counts('items'),

                TextColumn::make('verified_items_count')
                    ->label('Item Diverifikasi')
                    ->alignCenter()
                    ->counts('verifiedItems'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->alignCenter()
                    ->description(fn ($record) => $record->status->getDescription())
                    ->tooltip('Stok akan bertambah/berkurang ketika status=Selesai')
                    ->sortable(),
                TextColumn::make('received_at')
                    ->label('Tanggal Diterima')
                    ->placeholder('Belum diterima')
                    ->date('d F Y')
                    ->description(fn ($record) => $record->received_at?->format('H:i').' WIB')
                    ->sortable(),
            ])
            ->filters([
                DateRangeFilter::make('sent_at')
                    ->label('Periode Pengiriman')
                    ->placeholder('Pilih rentang tanggal'),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(DistributionStatus::class)
                    ->placeholder('Pilih status'),

            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    FinishDistributionAction::make(),
                ]),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(DistributionExporter::class)
                    ->columnMapping(false),
            ])
            ->bulkActions([
                ExportBulkAction::make()
                    ->exporter(DistributionExporter::class)
                    ->columnMapping(false),
            ]);
    }
}
