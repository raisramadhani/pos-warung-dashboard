<?php

namespace App\Filament\Admin\Resources\Merchants\RelationManagers;

use App\Enums\Inventories\DistributionStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class DistributionsRelationManager extends RelationManager
{
    protected static string $relationship = 'distributions';

    protected static ?string $recordTitleAttribute = 'id';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Distribusi';
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Distribusi')
            ->description('Riwayat distribusi ke outlet ini')
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex()
                    ->alignCenter()
                    ->width(10),
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('items_count')
                    ->label('Jumlah Item')
                    ->counts('items'),
                TextColumn::make('sent_at')
                    ->label('Dikirim')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('received_at')
                    ->label('Diterima')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(DistributionStatus::class)
                    ->placeholder('Pilih status'),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
