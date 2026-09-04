<?php

namespace App\Filament\Admin\Resources\Customers\Tables;

use App\Filament\Exports\CustomerExporter;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex()
                    ->sortable()
                    ->width(10)
                    ->alignCenter()
                    ->visibleFrom('md'),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('merchant.name')
                    ->label('Outlet')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('Telepon')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('transactions_count')
                    ->label('Transaksi')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('merchant_id')
                    ->label('Outlet')
                    ->relationship('merchant', 'name', fn ($query) => $query->merchantsOnly())
                    ->searchable()
                    ->preload()
                    ->placeholder('Pilih outlet'),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(CustomerExporter::class)
                    ->columnMapping(false),
            ])
            ->bulkActions([
                ExportBulkAction::make()
                    ->exporter(CustomerExporter::class)
                    ->columnMapping(false),
            ]);
    }
}
