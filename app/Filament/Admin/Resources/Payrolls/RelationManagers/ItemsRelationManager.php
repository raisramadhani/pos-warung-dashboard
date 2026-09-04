<?php

namespace App\Filament\Admin\Resources\Payrolls\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $recordTitleAttribute = 'component_name';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Komponen Gaji';
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Komponen Gaji')
            ->description('Daftar komponen gaji dalam slip ini')
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex()
                    ->alignCenter()
                    ->width(10),
                TextColumn::make('component_name')
                    ->label('Komponen')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('daily_rate')
                    ->label('Nilai Harian')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('days')
                    ->label('Jumlah Hari')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('amount')
                    ->label('Jumlah')
                    ->numeric()
                    ->sortable(),
            ]);
    }
}
