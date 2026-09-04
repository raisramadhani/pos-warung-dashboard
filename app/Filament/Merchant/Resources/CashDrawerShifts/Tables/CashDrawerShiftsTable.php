<?php

namespace App\Filament\Merchant\Resources\CashDrawerShifts\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CashDrawerShiftsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex()
                    ->width(10)
                    ->alignCenter()
                    ->visibleFrom('md'),
                TextColumn::make('shift_number')
                    ->label('No. Shift')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('opening_amount')
                    ->label('Saldo Awal')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('openedBy.name')
                    ->label('Dibuka Oleh')
                    ->placeholder('-'),
                TextColumn::make('opened_at')
                    ->label('Dibuka')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('closed_at')
                    ->label('Ditutup')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
