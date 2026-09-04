<?php

namespace App\Filament\Merchant\Resources\Customers;

use App\Filament\Merchant\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Merchant\Resources\Customers\Pages\EditCustomer;
use App\Filament\Merchant\Resources\Customers\Pages\ListCustomers;
use App\Filament\Merchant\Resources\Customers\Pages\ViewCustomer;
use App\Filament\Merchant\Resources\Customers\Schemas\CustomerForm;
use App\Filament\Merchant\Resources\Customers\Schemas\CustomerInfolist;
use App\Filament\Merchant\Resources\Customers\Tables\CustomersTable;
use App\Models\Customers\Customer;
use App\Models\Merchants\Merchant;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $modelLabel = 'Pelanggan';

    protected static ?string $pluralModelLabel = 'Pelanggan';

    protected static ?string $navigationLabel = 'Pelanggan';

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ViewCustomer::class,
            EditCustomer::class,
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return CustomerForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CustomerInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'view' => ViewCustomer::route('/{record}'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var Merchant|null $tenant */
        $tenant = filament()->getTenant();

        return parent::getEloquentQuery()
            ->where('merchant_id', $tenant?->id)
            ->withCount('transactions');
    }
}
