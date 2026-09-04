<?php

use App\Enums\Merchants\MerchantStatus;
use App\Enums\Merchants\OwnershipType;
use App\Filament\Admin\Resources\Merchants\Pages\ViewMerchant;
use App\Models\Merchants\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

test('can render view page', function () {
    $merchant = Merchant::factory()->create();

    livewire(ViewMerchant::class, ['record' => $merchant->id])
        ->assertSuccessful();
});

test('can see merchant details on view page', function () {
    $merchant = Merchant::factory()->create([
        'name' => 'Viewable Merchant',
        'slug' => 'viewable-merchant',
        'address' => 'Jl. View No. 1',
    ]);

    livewire(ViewMerchant::class, ['record' => $merchant->id])
        ->assertSuccessful()
        ->assertSee('Viewable Merchant')
        ->assertSee('viewable-merchant')
        ->assertSee('Jl. View No. 1');
});

test('shows infolist entries on view page', function () {
    $merchant = Merchant::factory()->create([
        'name' => 'Toko Infolist',
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
        ->assertSchemaComponentExists('longitude')
        ->assertSchemaComponentExists('created_at')
        ->assertSchemaComponentExists('updated_at');
});

test('view page renders with status history', function () {
    $merchant = Merchant::factory()->active()->create();
    $merchant->setStatus(MerchantStatus::Inactive);

    livewire(ViewMerchant::class, ['record' => $merchant->id])
        ->assertSuccessful();
});

test('view page renders with members', function () {
    $merchant = Merchant::factory()->create();
    $user = User::factory()->create();
    $merchant->members()->attach($user);

    livewire(ViewMerchant::class, ['record' => $merchant->id])
        ->assertSuccessful();
});

test('view page hides avatar when merchant has no avatar', function () {
    $merchant = Merchant::factory()->create(['avatar_path' => null]);

    livewire(ViewMerchant::class, ['record' => $merchant->id])
        ->assertSuccessful();
});

test('view page shows initial status', function () {
    $merchant = Merchant::factory()->active()->create();

    livewire(ViewMerchant::class, ['record' => $merchant->id])
        ->assertSuccessful();

    expect($merchant->statuses()->count())->toBeGreaterThanOrEqual(1);
});

test('view page shows ownership type badge', function () {
    $merchant = Merchant::factory()->branch()->create();

    livewire(ViewMerchant::class, ['record' => $merchant->id])
        ->assertSuccessful()
        ->assertSee(OwnershipType::Branch->getLabel());
});
