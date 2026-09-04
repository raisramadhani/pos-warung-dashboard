<?php

namespace App\Filament\Admin\Resources\Merchants;

use App\Filament\Admin\Resources\Merchants\Schemas\MerchantForm;
use App\Filament\Admin\Resources\Merchants\Schemas\MerchantInfolist;
use App\Filament\Admin\Resources\Merchants\Tables\MerchantsTable;
use App\Models\Merchants\Merchant;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MerchantResource extends Resource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $model = Merchant::class;

    protected static ?string $modelLabel = 'Outlet';

    protected static ?string $pluralModelLabel = 'Outlet';

    protected static ?string $navigationLabel = 'Outlet';

    protected static ?string $slug = 'merchants';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?int $navigationSort = 2;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ViewMerchant::class,
            Pages\EditMerchant::class,
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return MerchantForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MerchantsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MerchantInfolist::configure($schema);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\MembersRelationManager::class,
            // RelationManagers\StatusesRelationManager::class,
            // RelationManagers\StocksRelationManager::class,
            // RelationManagers\DistributionsRelationManager::class,
            // RelationManagers\TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMerchants::route('/'),
            'create' => Pages\CreateMerchant::route('/create'),
            'view' => Pages\ViewMerchant::route('/{record}'),
            'edit' => Pages\EditMerchant::route('/{record}/edit'),
        ];
    }
}
