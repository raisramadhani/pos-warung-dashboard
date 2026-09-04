<?php

namespace App\Filament\Merchant\Resources\Distributions;

use App\Filament\Merchant\Resources\Distributions\Schemas\DistributionInfolist;
use App\Filament\Merchant\Resources\Distributions\Tables\DistributionsTable;
use App\Models\Inventories\Distribution;
use App\Models\Merchants\Merchant;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DistributionResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = Distribution::class;

    protected static ?string $modelLabel = 'Distribusi';

    protected static ?string $pluralModelLabel = 'Distribusi';

    protected static ?string $navigationLabel = 'Distribusi Barang';

    protected static ?string $slug = 'distributions';

    protected static string|\UnitEnum|null $navigationGroup = 'Persediaan';

    protected static string|\BackedEnum|null $navigationIcon = 'tabler-truck-loading';

    protected static ?int $navigationSort = 1;

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
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return DistributionsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DistributionInfolist::configure($schema);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDistributions::route('/'),
            'view' => Pages\ViewDistribution::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var Merchant|null $tenant */
        $tenant = filament()->getTenant();

        return parent::getEloquentQuery()
            ->where('merchant_id', $tenant?->id);
    }
}
