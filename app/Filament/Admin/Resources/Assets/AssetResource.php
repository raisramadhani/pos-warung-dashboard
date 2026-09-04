<?php

namespace App\Filament\Admin\Resources\Assets;

use App\Filament\Admin\Resources\Assets\Schemas\AssetForm;
use App\Filament\Admin\Resources\Assets\Schemas\AssetInfolist;
use App\Filament\Admin\Resources\Assets\Tables\AssetsTable;
use App\Models\Inventories\Asset;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AssetResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static ?string $modelLabel = 'Aset';

    protected static ?string $pluralModelLabel = 'Aset';

    protected static ?string $navigationLabel = 'Aset';

    protected static ?string $slug = 'assets';

    protected static string|\UnitEnum|null $navigationGroup = 'Persediaan';

    protected static string|\BackedEnum|null $navigationIcon = 'tabler-asset';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ViewAsset::class,
            Pages\EditAsset::class,
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return AssetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssetsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AssetInfolist::configure($schema);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\DepreciationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssets::route('/'),
            'create' => Pages\CreateAsset::route('/create'),
            'view' => Pages\ViewAsset::route('/{record}'),
            'edit' => Pages\EditAsset::route('/{record}/edit'),
        ];
    }
}
