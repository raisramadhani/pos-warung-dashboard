<?php

use App\Enums\Merchants\MerchantStatus;
use App\Enums\Merchants\MerchantType;
use App\Enums\Merchants\OwnershipType;
use App\Filament\Admin\Resources\Merchants\Pages\CreateMerchant;
use App\Filament\Admin\Resources\Merchants\Pages\EditMerchant;
use App\Filament\Admin\Resources\Merchants\Pages\ListMerchants;
use App\Filament\Admin\Resources\Merchants\Pages\ViewMerchant;
use App\Models\Merchants\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render list page', function () {
    livewire(ListMerchants::class)
        ->assertSuccessful();
});

test('can render create page', function () {
    livewire(CreateMerchant::class)
        ->assertSuccessful();
});

test('can render edit page', function () {
    $merchant = Merchant::factory()->create();

    livewire(EditMerchant::class, ['record' => $merchant->id])
        ->assertSuccessful();
});

test('can render view page', function () {
    $merchant = Merchant::factory()->create();

    livewire(ViewMerchant::class, ['record' => $merchant->id])
        ->assertSuccessful();
});

test('view page shows infolist entries', function () {
    $merchant = Merchant::factory()->create([
        'name' => 'Toko Infolist',
        'type' => MerchantType::Merchant,
        'current_status' => MerchantStatus::Active,
        'ownership_type' => OwnershipType::Main,
        'address' => 'Jl. Infolist No. 1',
    ]);

    livewire(ViewMerchant::class, ['record' => $merchant->id])
        ->assertSuccessful()
        ->assertSchemaComponentExists('name')
        ->assertSchemaComponentExists('type')
        ->assertSchemaComponentExists('slug')
        ->assertSchemaComponentExists('current_status')
        ->assertSchemaComponentExists('ownership_type')
        ->assertSchemaComponentExists('address')
        ->assertSchemaComponentExists('latitude')
        ->assertSchemaComponentExists('longitude');
});

// ─── Sad Path ───────────────────────────────────────────

test('view page renders merchant without address', function () {
    $merchant = Merchant::factory()->create(['address' => null]);

    livewire(ViewMerchant::class, ['record' => $merchant->id])
        ->assertSuccessful();
});

// ─── Edge Cases ─────────────────────────────────────────

test('view page renders inactive merchant', function () {
    $merchant = Merchant::factory()->inactive()->create();

    livewire(ViewMerchant::class, ['record' => $merchant->id])
        ->assertSuccessful()
        ->assertSchemaComponentExists('current_status');
});
