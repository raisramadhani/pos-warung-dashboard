<?php

namespace App\Filament\Merchant\Resources\Promotions;

use App\Filament\Merchant\Resources\Promotions\Pages\CreatePromotion;
use App\Filament\Merchant\Resources\Promotions\Pages\EditPromotion;
use App\Filament\Merchant\Resources\Promotions\Pages\ListPromotions;
use App\Filament\Merchant\Resources\Promotions\Pages\ViewPromotion;
use App\Filament\Merchant\Resources\Promotions\Schemas\PromotionForm;
use App\Filament\Merchant\Resources\Promotions\Schemas\PromotionInfolist;
use App\Filament\Merchant\Resources\Promotions\Tables\PromotionsTable;
use App\Models\Merchants\Merchant;
use App\Models\Promotions\Promotion;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
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

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ViewPromotion::class,
            EditPromotion::class,
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return PromotionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PromotionInfolist::configure($schema);
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
            'create' => CreatePromotion::route('/create'),
            'view' => ViewPromotion::route('/{record}'),
            'edit' => EditPromotion::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var Merchant|null $tenant */
        $tenant = filament()->getTenant();

        return parent::getEloquentQuery()
            ->where('merchant_id', $tenant?->getKey())
            ->with(['schedules', 'conditions', 'rewards'])
            ->withCount('redemptions');
    }
}
