<?php

namespace App\Filament\Merchant\Resources\Categories\RelationManagers;

use App\Filament\Merchant\Resources\Products\ProductResource;
use App\Models\Products\Product;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Produk';
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Produk')
            ->description('Daftar produk dalam kategori ini')
            ->recordUrl(fn (Product $record) => ProductResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->sortable()
                    ->alignCenter()
                    ->width(10)
                    ->rowIndex()
                    ->visibleFrom('md'),
                TextColumn::make('name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('selling_price')
                    ->label('Harga Jual')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('cost_price')
                    ->label('Harga Beli')
                    ->numeric()
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label('Aktif'),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }

    protected function getTableQuery(): Builder
    {
        $tenant = filament()->getTenant();

        return Product::query()->where('merchant_id', $tenant?->getKey())
            ->where('category_id', $this->getOwnerRecord()->getKey());
    }
}
