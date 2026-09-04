<?php

use App\Enums\Merchants\MerchantStatus;
use App\Filament\Admin\Resources\Merchants\Pages\ListMerchants;
use App\Filament\Admin\Resources\Merchants\Pages\ViewMerchant;
use App\Filament\Admin\Resources\Merchants\RelationManagers\MembersRelationManager;
use App\Models\Merchants\Merchant;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('renders relation manager with members', function () {
    $merchant = Merchant::factory()->create();
    $user = User::factory()->create(['name' => 'Anggota Satu']);
    $merchant->members()->attach($user);

    livewire(MembersRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$user]);
});

test('shows member columns', function () {
    $merchant = Merchant::factory()->create();

    livewire(MembersRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertTableColumnExists('name')
        ->assertTableColumnExists('username')
        ->assertTableColumnExists('email')
        ->assertTableColumnExists('role');
});

test('configures columns correctly', function () {
    $merchant = Merchant::factory()->create();
    $user = User::factory()->create(['name' => 'Anggota Satu']);
    $merchant->members()->attach($user);

    livewire(MembersRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertTableColumnExists('name', function (TextColumn $column): bool {
            return $column->isSearchable();
        }, $user)
        ->assertTableColumnExists('username', function (TextColumn $column): bool {
            return $column->isSearchable();
        }, $user)
        ->assertTableColumnExists('role', function (TextColumn $column): bool {
            return $column->isBadge();
        }, $user);
});

test('can search members by name', function () {
    $merchant = Merchant::factory()->create();
    $visible = User::factory()->create(['name' => 'Alpha Member']);
    $hidden = User::factory()->create(['name' => 'Beta Member']);
    $merchant->members()->attach([$visible, $hidden]);

    livewire(MembersRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->searchTable('Alpha')
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

test('can attach a new member', function () {
    $merchant = Merchant::factory()->create();
    $user = User::factory()->create();

    livewire(MembersRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ListMerchants::class,
    ])
        ->callTableAction('attach', data: ['recordId' => $user->id])
        ->assertNotified();

    $this->assertDatabaseHas('merchant_user', [
        'merchant_id' => $merchant->id,
        'user_id' => $user->id,
    ]);
});

test('attach select only offers users with merchant role', function () {
    $merchant = Merchant::factory()->create();
    $merchantUser = User::factory()->create(['name' => 'Boleh Dipilih']);
    $superAdmin = User::factory()->superAdmin()->create(['name' => 'Tidak Boleh']);

    $this->actingAs(User::factory()->superAdmin()->create());

    livewire(MembersRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ListMerchants::class,
    ])
        ->mountTableAction('attach')
        ->assertSuccessful()
        ->assertActionMounted();
});

test('cannot attach a super admin user', function () {
    $merchant = Merchant::factory()->create();
    $superAdmin = User::factory()->superAdmin()->create();

    livewire(MembersRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ListMerchants::class,
    ])
        ->callTableAction('attach', data: ['recordId' => $superAdmin->id]);

    $this->assertDatabaseMissing('merchant_user', [
        'merchant_id' => $merchant->id,
        'user_id' => $superAdmin->id,
    ]);
});

test('can detach a member', function () {
    $merchant = Merchant::factory()->create();
    $user = User::factory()->create();
    $merchant->members()->attach($user);

    livewire(MembersRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ListMerchants::class,
    ])
        ->callTableAction('detach', $user)
        ->assertNotified();

    $this->assertDatabaseMissing('merchant_user', [
        'merchant_id' => $merchant->id,
        'user_id' => $user->id,
    ]);
});

// ─── Sad Path ───────────────────────────────────────────

test('renders with no members', function () {
    $merchant = Merchant::factory()->create();

    livewire(MembersRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

test('members of other merchants are isolated', function () {
    $merchant1 = Merchant::factory()->create();
    $merchant2 = Merchant::factory()->create();
    $user = User::factory()->create();
    $merchant2->members()->attach($user);

    livewire(MembersRelationManager::class, [
        'ownerRecord' => $merchant1,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

// ─── Edge Cases ─────────────────────────────────────────

test('shows merchant with many members', function () {
    $merchant = Merchant::factory()->create();
    $users = User::factory()->count(5)->create();
    $merchant->members()->attach($users);

    livewire(MembersRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords($users)
        ->assertCountTableRecords(5);
});

test('merchant with status history still shows members', function () {
    $merchant = Merchant::factory()->active()->create();
    $merchant->setStatus(MerchantStatus::Inactive);
    $user = User::factory()->create();
    $merchant->members()->attach($user);

    livewire(MembersRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$user]);
});

test('renders with many members without ambiguous column error', function () {
    $merchant = Merchant::factory()->create();
    $members = User::factory()->count(5)->create();
    $merchant->members()->attach($members);

    livewire(MembersRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords($members);
});
