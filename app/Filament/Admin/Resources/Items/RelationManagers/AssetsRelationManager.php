<?php

namespace App\Filament\Admin\Resources\Items\RelationManagers;

use App\Enums\Inventories\AssetStatus;
use BackedEnum;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AssetsRelationManager extends RelationManager
{
    protected static string $relationship = 'assets';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $icon = Heroicon::ArchiveBox;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Aset';
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Daftar Aset')
            ->description('Aset yang terkait dengan item ini')
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex()
                    ->alignCenter()
                    ->width(10),
                TextColumn::make('acquisition_date')
                    ->label('Tanggal Akuisisi')
                    ->date()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('acquisition_cost')
                    ->label('Harga Akuisisi')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('current_book_value')
                    ->label('Nilai Buku Saat Ini')
                    ->state(fn ($record) => $record->current_book_value)
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
            ])
            ->defaultSort('acquisition_date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(AssetStatus::class)
                    ->placeholder('Pilih status'),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
