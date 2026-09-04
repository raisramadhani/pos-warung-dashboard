<?php

use App\Filament\Merchant\Resources\Customers\Pages\ViewCustomer;
use App\Models\Customers\Customer;
use App\Models\Merchants\Merchant;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render view page', function () {
    $customer = Customer::factory()->forMerchant($this->merchant)->create();

    livewire(ViewCustomer::class, ['record' => $customer->id])
        ->assertSuccessful();
});

test('shows infolist entries on view page', function () {
    $customer = Customer::factory()->forMerchant($this->merchant)->create([
        'name' => 'Budi Santoso',
        'phone' => '08123456789',
    ]);

    livewire(ViewCustomer::class, ['record' => $customer->id])
        ->assertSuccessful()
        ->assertSee('Budi Santoso')
        ->assertSee('08123456789')
        ->assertSchemaComponentExists('name')
        ->assertSchemaComponentExists('phone')
        ->assertSchemaComponentExists('created_at')
        ->assertSchemaComponentExists('updated_at');
});

test('has edit action on view page', function () {
    $customer = Customer::factory()->forMerchant($this->merchant)->create();

    livewire(ViewCustomer::class, ['record' => $customer->id])
        ->assertActionExists('edit');
});

// ─── Sad Path ───────────────────────────────────────────

test('view page renders customer without optional fields', function () {
    $customer = Customer::factory()->forMerchant($this->merchant)->create([
        'phone' => null,
    ]);

    livewire(ViewCustomer::class, ['record' => $customer->id])
        ->assertSuccessful();
});

test('cannot view a customer from another merchant', function () {
    $otherMerchant = Merchant::factory()->active()->create();
    $otherCustomer = Customer::factory()->forMerchant($otherMerchant)->create();

    $this->expectException(ModelNotFoundException::class);

    livewire(ViewCustomer::class, ['record' => $otherCustomer->id]);
});

// ─── Edge Cases ─────────────────────────────────────────

test('view page renders customer without transactions', function () {
    $customer = Customer::factory()->forMerchant($this->merchant)->create();

    livewire(ViewCustomer::class, ['record' => $customer->id])
        ->assertSuccessful();
});
