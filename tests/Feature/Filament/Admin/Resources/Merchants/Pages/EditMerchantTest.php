<?php

use App\Enums\Merchants\MerchantStatus;
use App\Enums\Merchants\OwnershipType;
use App\Filament\Admin\Resources\Merchants\Pages\EditMerchant;
use App\Models\Merchants\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

test('can render edit page', function () {
    $merchant = Merchant::factory()->create();

    livewire(EditMerchant::class, ['record' => $merchant->id])
        ->assertSuccessful();
});

test('can update merchant name', function () {
    $merchant = Merchant::factory()->create(['name' => 'Old Name']);

    livewire(EditMerchant::class, ['record' => $merchant->id])
        ->fillForm(['name' => 'Updated Name'])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($merchant->fresh()->name)->toBe('Updated Name');
});

test('can update merchant address', function () {
    $merchant = Merchant::factory()->create();

    livewire(EditMerchant::class, ['record' => $merchant->id])
        ->fillForm(['address' => 'Jl. Baru No. 99'])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($merchant->fresh()->address)->toBe('Jl. Baru No. 99');
});

test('can update ownership type', function () {
    $merchant = Merchant::factory()->main()->create();

    livewire(EditMerchant::class, ['record' => $merchant->id])
        ->fillForm(['ownership_type' => OwnershipType::Branch->value])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($merchant->fresh()->ownership_type)->toBe(OwnershipType::Branch);
});

test('can update merchant status', function () {
    $merchant = Merchant::factory()->inactive()->create();

    livewire(EditMerchant::class, ['record' => $merchant->id])
        ->fillForm(['current_status' => MerchantStatus::Active->value])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $merchant->refresh();

    expect($merchant->current_status)->toBe(MerchantStatus::Active);

    $latestStatus = $merchant->statuses()->latest('id')->first();
    expect($latestStatus->name)->toBe(MerchantStatus::Active->value);
});

test('edit validates name is required', function () {
    $merchant = Merchant::factory()->create();

    livewire(EditMerchant::class, ['record' => $merchant->id])
        ->fillForm(['name' => ''])
        ->call('save')
        ->assertHasFormErrors(['name' => 'required']);
});

test('edit validates slug is unique ignoring own record', function () {
    $merchant = Merchant::factory()->create(['slug' => 'toko-saya']);

    livewire(EditMerchant::class, ['record' => $merchant->id])
        ->fillForm(['slug' => 'toko-saya'])
        ->call('save')
        ->assertHasNoFormErrors();
});

test('edit ignores slug input and keeps existing slug', function () {
    $merchant = Merchant::factory()->create([
        'name' => 'Original Name',
        'slug' => 'original-slug',
    ]);

    livewire(EditMerchant::class, ['record' => $merchant->id])
        ->fillForm(['slug' => 'Slug Salah!'])
        ->call('save')
        ->assertHasNoFormErrors();

    $merchant->refresh();
    expect($merchant->slug)->toBe('original-slug');
});

test('edit slug is not updated when value changes', function () {
    $merchant = Merchant::factory()->create([
        'name' => 'Original Name',
        'slug' => 'original-slug',
    ]);

    livewire(EditMerchant::class, ['record' => $merchant->id])
        ->fillForm([
            'name' => 'Updated Name',
            'slug' => 'updated-slug',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $merchant->refresh();
    expect($merchant->slug)->toBe('original-slug');
    expect($merchant->name)->toBe('Updated Name');
});

test('edit status creates history record for each change', function () {
    $merchant = Merchant::factory()->active()->create();
    $initialCount = $merchant->statuses()->count();

    livewire(EditMerchant::class, ['record' => $merchant->id])
        ->fillForm(['current_status' => MerchantStatus::Inactive->value])
        ->call('save')
        ->assertNotified();

    expect($merchant->fresh()->statuses()->count())->toBe($initialCount + 1);

    livewire(EditMerchant::class, ['record' => $merchant->id])
        ->fillForm(['current_status' => MerchantStatus::Active->value])
        ->call('save')
        ->assertNotified();

    expect($merchant->fresh()->statuses()->count())->toBe($initialCount + 2);
});

test('edit does not create duplicate status record when status not changed', function () {
    $merchant = Merchant::factory()->active()->create();
    $initialCount = $merchant->statuses()->count();

    livewire(EditMerchant::class, ['record' => $merchant->id])
        ->fillForm(['name' => 'Just Renamed'])
        ->call('save')
        ->assertNotified();

    expect($merchant->fresh()->statuses()->count())->toBe($initialCount);
});

test('can soft delete merchant from edit page', function () {
    $merchant = Merchant::factory()->create();

    livewire(EditMerchant::class, ['record' => $merchant->id])
        ->callAction('delete')
        ->assertNotified();

    $this->assertSoftDeleted('merchants', ['id' => $merchant->id]);
});
