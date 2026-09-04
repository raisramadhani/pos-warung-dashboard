<?php

namespace App\Filament\Admin\Resources\Suppliers\Tables;

use App\Filament\Exports\SupplierExporter;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SuppliersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name', 'asc')
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->sortable()
                    ->alignCenter()
                    ->width(10)
                    ->rowIndex()
                    ->visibleFrom('md'),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                // TextColumn::make('merchant.name')
                //     ->label('Merchant')
                //     ->searchable()
                //     ->sortable()
                //     ->placeholder('-'),
                TextColumn::make('contact_person')
                    ->label('Kontak')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('phone')
                    ->label('Telepon')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('purchase_orders_count')
                    ->label('Jumlah PO')
                    ->sortable()
                    ->alignCenter(),
                // TextColumn::make('purchase_orders_max_finished_at')
                //     ->label('Terakhir Selesai')
                //     ->dateTime()
                //     ->sortable()
                //     ->placeholder('-'),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif'),
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
                    ->exporter(SupplierExporter::class)
                    ->columnMapping(false),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(SupplierExporter::class)
                        ->columnMapping(false),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
