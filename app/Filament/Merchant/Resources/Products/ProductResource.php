<?php

namespace App\Filament\Merchant\Resources\Products;

use App\Filament\Merchant\Resources\Products\Pages\CreateProduct;
use App\Filament\Merchant\Resources\Products\Pages\EditProduct;
use App\Filament\Merchant\Resources\Products\Pages\ListProducts;
use App\Filament\Merchant\Resources\Products\Pages\ViewProduct;
use App\Filament\Merchant\Resources\Products\Schemas\ProductForm;
use App\Filament\Merchant\Resources\Products\Schemas\ProductInfolist;
use App\Filament\Merchant\Resources\Products\Tables\ProductsTable;
use App\Models\Merchants\Merchant;
use App\Models\Products\Product;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $modelLabel = 'Produk';

    protected static ?string $pluralModelLabel = 'Produk';

    protected static ?string $navigationLabel = 'Produk';

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ViewProduct::class,
            EditProduct::class,
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProductInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'view' => ViewProduct::route('/{record}'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var Merchant|null $tenant */
        $tenant = filament()->getTenant();

        return parent::getEloquentQuery()
            ->where('merchant_id', $tenant?->getKey())
            ->with(['category', 'productMaterials.item']);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->with(['productMaterials.item']);
    }
}
