<?php

use App\Enums\Merchants\MerchantStatus;
use App\Filament\Admin\Resources\Users\Pages\ViewUser;
use App\Filament\Admin\Resources\Users\RelationManagers\MerchantsRelationManager;
use App\Models\Merchants\Merchant;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('renders relation manager with merchants', function () {
    $user = User::factory()->create();
    $merchants = Merchant::factory()->active()->count(2)->create();
    $user->merchants()->attach($merchants);

    livewire(MerchantsRelationManager::class, [
        'ownerRecord' => $user,
        'pageClass' => ViewUser::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords($merchants)
        ->assertCountTableRecords(2);
});

test('shows merchant columns', function () {
    $user = User::factory()->create();

    livewire(MerchantsRelationManager::class, [
        'ownerRecord' => $user,
        'pageClass' => ViewUser::class,
    ])
        ->assertSuccessful()
        ->assertTableColumnExists('name')
        ->assertTableColumnExists('slug')
        ->assertTableColumnExists('current_status')
        ->assertTableColumnExists('members_count');
});

test('configures columns correctly', function () {
    $user = User::factory()->create();
    $merchant = Merchant::factory()->active()->create(['name' => 'Toko Utama']);
    $user->merchants()->attach($merchant);

    livewire(MerchantsRelationManager::class, [
        'ownerRecord' => $user,
        'pageClass' => ViewUser::class,
    ])
        ->assertSuccessful()
        ->assertTableColumnExists('name', function (TextColumn $column): bool {
            return $column->isSearchable() && $column->isSortable();
        }, $merchant)
        ->assertTableColumnExists('slug', function (TextColumn $column): bool {
            return $column->isSearchable();
        }, $merchant)
        ->assertTableColumnExists('current_status', function (TextColumn $column): bool {
            return $column->isBadge() && $column->isSortable();
        }, $merchant)
        ->assertTableColumnExists('members_count', function (TextColumn $column): bool {
            return $column->isSortable();
        }, $merchant);
});

test('can search merchants by name', function () {
    $user = User::factory()->create();
    $visible = Merchant::factory()->active()->create(['name' => 'Alpha Outlet']);
    $hidden = Merchant::factory()->active()->create(['name' => 'Beta Outlet']);
    $user->merchants()->attach([$visible, $hidden]);

    livewire(MerchantsRelationManager::class, [
        'ownerRecord' => $user,
        'pageClass' => ViewUser::class,
    ])
        ->searchTable('Alpha')
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

test('can filter by merchant status', function () {
    $user = User::factory()->create();
    $active = Merchant::factory()->active()->create(['name' => 'Aktif']);
    $inactive = Merchant::factory()->inactive()->create(['name' => 'Nonaktif']);
    $user->merchants()->attach([$active, $inactive]);

    livewire(MerchantsRelationManager::class, [
        'ownerRecord' => $user,
        'pageClass' => ViewUser::class,
    ])
        ->filterTable('current_status', MerchantStatus::Active->value)
        ->assertCanSeeTableRecords([$active])
        ->assertCanNotSeeTableRecords([$inactive]);
});

test('shows merchants across different statuses', function () {
    $user = User::factory()->create();
    $active = Merchant::factory()->active()->create(['name' => 'Aktif']);
    $inactive = Merchant::factory()->inactive()->create(['name' => 'Nonaktif']);
    $user->merchants()->attach([$active->id, $inactive->id]);

    livewire(MerchantsRelationManager::class, [
        'ownerRecord' => $user,
        'pageClass' => ViewUser::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(2);
});

test('has attach header action', function () {
    $user = User::factory()->create();

    livewire(MerchantsRelationManager::class, [
        'ownerRecord' => $user,
        'pageClass' => ViewUser::class,
    ])
        ->assertSuccessful()
        ->assertTableActionExists('attach');
});

// ─── Sad Path ────────────────────────────────────────────

test('renders with no attached merchants', function () {
    $user = User::factory()->create();

    livewire(MerchantsRelationManager::class, [
        'ownerRecord' => $user,
        'pageClass' => ViewUser::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

test('merchants from other users are isolated', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $merchant = Merchant::factory()->active()->create();
    $user2->merchants()->attach($merchant);

    livewire(MerchantsRelationManager::class, [
        'ownerRecord' => $user1,
        'pageClass' => ViewUser::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

// ─── Edge Cases ──────────────────────────────────────────

test('detached merchant no longer appears', function () {
    $user = User::factory()->create();
    $m1 = Merchant::factory()->active()->create(['name' => 'Toko Tetap']);
    $m2 = Merchant::factory()->active()->create(['name' => 'Toko Lepas']);
    $user->merchants()->attach([$m1->id, $m2->id]);
    $user->merchants()->detach($m2->id);

    livewire(MerchantsRelationManager::class, [
        'ownerRecord' => $user,
        'pageClass' => ViewUser::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(1);
});
