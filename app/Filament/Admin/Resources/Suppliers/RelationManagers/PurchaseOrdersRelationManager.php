<?php

namespace App\Filament\Admin\Resources\Suppliers\RelationManagers;

use App\Enums\Inventories\PurchaseOrderStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'purchaseOrders';

    protected static ?string $recordTitleAttribute = 'po_number';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Purchase Orders';
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Purchase Orders')
            ->description('Daftar purchase order dari supplier ini')
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex()
                    ->width(10)
                    ->alignCenter(),
                TextColumn::make('po_number')
                    ->label('No. PO')
                    ->description(fn ($record) => $record->created_at?->format('d M Y'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('finished_at')
                    ->label('Selesai')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(PurchaseOrderStatus::class)
                    ->placeholder('Pilih status'),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
