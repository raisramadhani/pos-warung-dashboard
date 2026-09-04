<?php

namespace App\Filament\Admin\Resources\Merchants\Tables;

use App\Enums\Merchants\MerchantStatus;
use App\Enums\Merchants\MerchantType;
use App\Enums\Merchants\OwnershipType;
use App\Filament\Exports\MerchantExporter;
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

class MerchantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
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
                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ownership_type')
                    ->label('Kepemilikan')
                    ->badge()
                    ->sortable(),
                TextColumn::make('members_count')
                    ->counts('members')
                    ->label('Anggota')
                    ->sortable(),
                TextColumn::make('current_status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('address')
                    ->label('Alamat')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(50),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipe')
                    ->options(MerchantType::class)
                    ->placeholder('Pilih tipe'),
                SelectFilter::make('current_status')
                    ->label('Status')
                    ->options(MerchantStatus::class)
                    ->placeholder('Pilih status'),
                SelectFilter::make('ownership_type')
                    ->label('Kepemilikan')
                    ->options(OwnershipType::class)
                    ->placeholder('Pilih kepemilikan'),

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
                    ->exporter(MerchantExporter::class)
                    ->columnMapping(false),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(MerchantExporter::class)
                        ->columnMapping(false),
                    DeleteBulkAction::make(),

                ]),
            ]);
    }
}
