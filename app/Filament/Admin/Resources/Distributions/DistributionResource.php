<?php

namespace App\Filament\Admin\Resources\Distributions;

use App\Filament\Admin\Resources\Distributions\Schemas\DistributionForm;
use App\Filament\Admin\Resources\Distributions\Schemas\DistributionInfolist;
use App\Filament\Admin\Resources\Distributions\Tables\DistributionsTable;
use App\Models\Inventories\Distribution;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DistributionResource extends Resource
{
    protected static ?string $model = Distribution::class;

    protected static ?string $modelLabel = 'Distribusi';

    protected static ?string $pluralModelLabel = 'Distribusi';

    protected static ?string $navigationLabel = 'Distribusi Barang';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'distributions';

    protected static string|\UnitEnum|null $navigationGroup = 'Persediaan';

    protected static string|\BackedEnum|null $navigationIcon = 'tabler-truck-loading';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            // Pages\ViewDistribution::class,
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return DistributionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DistributionsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DistributionInfolist::configure($schema);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDistributions::route('/'),
            'create' => Pages\CreateDistribution::route('/create'),
            'view' => Pages\ViewDistribution::route('/{record}'),
            'edit' => Pages\EditDistribution::route('/{record}/edit'),
        ];
    }
}
