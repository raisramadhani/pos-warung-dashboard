<?php

use App\Enums\Merchants\MerchantStatus;
use App\Enums\Merchants\OwnershipType;
use App\Enums\RoleType;
use App\Filament\Admin\Resources\Merchants\Pages\ListMerchants;
use App\Models\Merchants\Merchant;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render list page', function () {
    livewire(ListMerchants::class)
        ->assertSuccessful();
});

test('can list merchants', function () {
    $merchants = Merchant::factory()->count(3)->create();

    livewire(ListMerchants::class)
        ->assertCanSeeTableRecords($merchants)
        ->assertCountTableRecords(3);
});

test('can search merchants by name', function () {
    $visible = Merchant::factory()->create(['name' => 'Alpha Store']);
    $hidden = Merchant::factory()->create(['name' => 'Beta Store']);

    livewire(ListMerchants::class)
        ->searchTable('Alpha')
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

test('can search merchants by slug', function () {
    $visible = Merchant::factory()->create(['slug' => 'alpha-store']);
    $hidden = Merchant::factory()->create(['slug' => 'beta-store']);

    livewire(ListMerchants::class)
        ->searchTable('alpha-store')
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

test('can sort by name ascending', function () {
    $alpha = Merchant::factory()->create(['name' => 'Alpha Store']);
    $beta = Merchant::factory()->create(['name' => 'Beta Store']);

    livewire(ListMerchants::class)
        ->sortTable('name')
        ->assertCanSeeTableRecords([$alpha, $beta])
        ->assertCountTableRecords(2);
});

test('can filter merchants by status', function () {
    $active = Merchant::factory()->active()->create(['name' => 'Active Merchant']);
    $inactive = Merchant::factory()->inactive()->create(['name' => 'Inactive Merchant']);

    livewire(ListMerchants::class)
        ->filterTable('current_status', MerchantStatus::Active->value)
        ->assertCanSeeTableRecords([$active])
        ->assertCanNotSeeTableRecords([$inactive]);
});

test('can filter merchants by ownership type', function () {
    $direct = Merchant::factory()->main()->create(['name' => 'Direct Merchant']);
    $thirdParty = Merchant::factory()->branch()->create(['name' => 'Third Party Merchant']);

    livewire(ListMerchants::class)
        ->filterTable('ownership_type', OwnershipType::Main->value)
        ->assertCanSeeTableRecords([$direct])
        ->assertCanNotSeeTableRecords([$thirdParty]);
});

test('has create action', function () {
    livewire(ListMerchants::class)
        ->assertActionExists('create');
});

test('has export header action and bulk action', function () {
    livewire(ListMerchants::class)
        ->assertTableActionExists('export')
        ->assertTableBulkActionExists('export');
});

// ─── Sad Path ───────────────────────────────────────────

test('renders empty table when no merchants', function () {
    livewire(ListMerchants::class)
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

test('search returns no records when nothing matches', function () {
    Merchant::factory()->count(2)->create();

    livewire(ListMerchants::class)
        ->searchTable('tidak-ada-matching')
        ->assertCountTableRecords(0);
});

// ─── Edge Cases ─────────────────────────────────────────

test('trashed merchants are not visible in the list', function () {
    $trashed = Merchant::factory()->create();
    $trashed->delete();

    livewire(ListMerchants::class)
        ->assertCanNotSeeTableRecords([$trashed])
        ->assertCountTableRecords(0);
});

test('can soft delete merchant from list page', function () {
    $merchant = Merchant::factory()->create();

    livewire(ListMerchants::class)
        ->callAction(TestAction::make('delete')->table($merchant))
        ->assertNotified();

    $this->assertSoftDeleted('merchants', ['id' => $merchant->id]);
});

test('configures table columns correctly', function () {
    $merchant = Merchant::factory()->create();

    livewire(ListMerchants::class)
        ->assertTableColumnExists('name', function (TextColumn $column): bool {
            return $column->isSearchable() && $column->isSortable();
        }, $merchant)
        ->assertTableColumnExists('type', function (TextColumn $column): bool {
            return $column->isBadge() && $column->isSortable();
        }, $merchant)
        ->assertTableColumnExists('slug', function (TextColumn $column): bool {
            return $column->isSearchable() && $column->isSortable() && $column->isToggleable();
        }, $merchant)
        ->assertTableColumnExists('current_status', function (TextColumn $column): bool {
            return $column->isBadge() && $column->isSortable();
        }, $merchant)
        ->assertTableColumnExists('ownership_type', function (TextColumn $column): bool {
            return $column->isBadge() && $column->isSortable();
        }, $merchant)
        ->assertTableColumnExists('members_count', function (TextColumn $column): bool {
            return $column->isSortable();
        }, $merchant)
        ->assertTableColumnExists('created_at', function (TextColumn $column): bool {
            return $column->isSortable() && $column->isToggleable();
        }, $merchant);
});

test('merchant role user cannot access admin merchants', function () {
    $merchantUser = User::factory()->create(['role' => RoleType::Merchant]);

    $this->actingAs($merchantUser)
        ->get('/admin/merchants')
        ->assertForbidden();
});
