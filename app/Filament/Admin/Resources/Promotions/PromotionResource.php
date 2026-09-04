<?php

namespace App\Filament\Admin\Resources\Promotions;

use App\Filament\Admin\Resources\Promotions\Pages\ListPromotions;
use App\Filament\Admin\Resources\Promotions\Tables\PromotionsTable;
use App\Models\Merchants\Merchant;
use App\Models\Promotions\Promotion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PromotionResource extends Resource
{
    protected static ?string $model = Promotion::class;

    protected static ?string $modelLabel = 'Promo';

    protected static ?string $pluralModelLabel = 'Promo';

    protected static ?string $navigationLabel = 'Promo';

    protected static string|UnitEnum|null $navigationGroup = 'Data Outlet';

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return PromotionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPromotions::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('merchant', function (Builder $query): void {
                /** @var Builder<Merchant> $query */
                $query->merchantsOnly();
            })
            ->with(['schedules', 'conditions', 'rewards'])
            ->withCount('redemptions');
    }

    /**
     * Admin panel tidak menerapkan default outlet yang terseleksi.
     */
    public static function defaultOutletId(): ?int
    {
        return null;
    }
}
