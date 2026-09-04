<?php

namespace App\Filament\Admin\Resources\Assets\RelationManagers;

use App\Filament\Exports\AssetDepreciationExporter;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class DepreciationsRelationManager extends RelationManager
{
    protected static string $relationship = 'depreciations';

    protected static ?string $recordTitleAttribute = 'id';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Depresiasi';
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Riwayat Depresiasi')
            ->description('Riwayat depresiasi aset ini')
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex()
                    ->alignCenter()
                    ->width(10),
                TextColumn::make('period_date')
                    ->label('Periode')
                    ->date('F Y')
                    ->sortable(),
                TextColumn::make('depreciation_amount')
                    ->label('Jumlah Depresiasi')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('book_value_before')
                    ->label('Nilai Buku Sebelum')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('book_value_after')
                    ->label('Nilai Buku Setelah')
                    ->numeric()
                    ->sortable(),
            ])
            ->defaultSort('period_date', 'desc')
            ->filters([])
            ->headerActions([
                ExportAction::make()
                    ->exporter(AssetDepreciationExporter::class)
                    ->columnMapping(false),
            ])
            ->actions([])
            ->bulkActions([
                ExportBulkAction::make()
                    ->exporter(AssetDepreciationExporter::class)
                    ->columnMapping(false),
            ]);
    }
}
