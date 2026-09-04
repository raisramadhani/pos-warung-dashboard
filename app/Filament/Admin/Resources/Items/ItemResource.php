<?php

namespace App\Filament\Admin\Resources\Items;

use App\Filament\Admin\Resources\Items\Schemas\ItemForm;
use App\Filament\Admin\Resources\Items\Schemas\ItemInfolist;
use App\Filament\Admin\Resources\Items\Tables\ItemsTable;
use App\Models\Inventories\Item;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ItemResource extends Resource
{
    protected static ?string $model = Item::class;

    protected static ?string $modelLabel = 'Item / Barang';

    protected static ?string $pluralModelLabel = 'Item / Barang';

    protected static ?string $navigationLabel = 'Item / Barang';

    protected static ?string $slug = 'items';

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ViewItem::class,
            Pages\EditItem::class,
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return ItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ItemsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ItemInfolist::configure($schema);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\PurchaseOrderItemsRelationManager::class,
            // RelationManagers\DistributionItemsRelationManager::class,
            // RelationManagers\AssetsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListItems::route('/'),
            'create' => Pages\CreateItem::route('/create'),
            'view' => Pages\ViewItem::route('/{record}'),
            'edit' => Pages\EditItem::route('/{record}/edit'),
        ];
    }
}
