<?php

use App\Enums\Inventories\StockOpnameStatus;
use App\Filament\Merchant\Resources\StockOpnames\Pages\CreateStockOpname;
use App\Models\Inventories\Item;
use App\Models\Inventories\MerchantStock;
use App\Models\Inventories\StockOpname;
use App\Models\Merchants\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render create page', function () {
    livewire(CreateStockOpname::class)
        ->assertSuccessful();
});

test('can create new stock opname when no open opname exists', function () {
    livewire(CreateStockOpname::class)
        ->fillForm([
            'notes' => 'Opname bulanan',
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();

    expect(StockOpname::where('merchant_id', $this->merchant->id)->count())->toBe(1);
});

test('new stock opname has Counting status after creation', function () {
    livewire(CreateStockOpname::class)
        ->fillForm([
            'notes' => 'Opname awal',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $opname = StockOpname::where('merchant_id', $this->merchant->id)->first();
    expect($opname->status)->toBe(StockOpnameStatus::Counting);
});

test('new stock opname stores correct merchant_id and created_by', function () {
    livewire(CreateStockOpname::class)
        ->fillForm([
            'notes' => 'Test',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $opname = StockOpname::where('merchant_id', $this->merchant->id)->first();
    expect($opname->merchant_id)->toBe($this->merchant->id);
    expect($opname->created_by)->toBe($this->user->id);
});

test('can create stock opname with all items', function () {
    $item1 = Item::factory()->create();
    $item2 = Item::factory()->create();

    MerchantStock::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $item1->id,
        'quantity' => 10,
    ]);

    MerchantStock::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $item2->id,
        'quantity' => 20,
    ]);

    livewire(CreateStockOpname::class)
        ->fillForm([
            'is_lock_transactions' => 1,
            'opname_all_items' => 1,
            'notes' => 'Test notes',
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $this->assertDatabaseHas('stock_opnames', [
        'merchant_id' => $this->merchant->id,
        'is_lock_transactions' => true,
        'notes' => 'Test notes',
        'created_by' => $this->user->id,
    ]);

    $opname = StockOpname::latest()->first();
    expect($opname->items)->toHaveCount(2);
    expect($opname->items->pluck('item_id')->toArray())->toEqualCanonicalizing([$item1->id, $item2->id]);
});

test('can create stock opname with manual items selection', function () {
    $item1 = Item::factory()->create();
    $item2 = Item::factory()->create();

    MerchantStock::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $item1->id,
        'quantity' => 10,
    ]);

    MerchantStock::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $item2->id,
        'quantity' => 20,
    ]);

    livewire(CreateStockOpname::class)
        ->fillForm([
            'is_lock_transactions' => 0,
            'opname_all_items' => 0,
            'items' => [
                ['item_id' => $item1->id],
            ],
            'notes' => 'Manual selection notes',
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $this->assertDatabaseHas('stock_opnames', [
        'merchant_id' => $this->merchant->id,
        'is_lock_transactions' => false,
        'notes' => 'Manual selection notes',
        'created_by' => $this->user->id,
    ]);

    $opname = StockOpname::latest()->first();
    expect($opname->items)->toHaveCount(1);
    expect($opname->items->first()->item_id)->toBe($item1->id);
});

// ─── Sad Path ───────────────────────────────────────────

test('accessing create page redirects when Draft opname exists', function () {
    StockOpname::factory()
        ->for($this->merchant, 'merchant')
        ->for($this->user, 'creator')
        ->create(['status' => StockOpnameStatus::Draft]);

    livewire(CreateStockOpname::class)
        ->assertRedirect();
});

test('accessing create page redirects when Counting opname exists', function () {
    StockOpname::factory()
        ->for($this->merchant, 'merchant')
        ->for($this->user, 'creator')
        ->counting()
        ->create();

    livewire(CreateStockOpname::class)
        ->assertRedirect();
});

test('accessing create page redirects when Reconciling opname exists', function () {
    StockOpname::factory()
        ->for($this->merchant, 'merchant')
        ->for($this->user, 'creator')
        ->reconciling()
        ->create();

    livewire(CreateStockOpname::class)
        ->assertRedirect();
});

test('redirected user receives warning notification', function () {
    StockOpname::factory()
        ->for($this->merchant, 'merchant')
        ->for($this->user, 'creator')
        ->create(['status' => StockOpnameStatus::Draft]);

    livewire(CreateStockOpname::class)
        ->assertNotified();
});

// ─── Edge Cases ─────────────────────────────────────────

test('can create new opname after previous one is Completed', function () {
    StockOpname::factory()
        ->for($this->merchant, 'merchant')
        ->for($this->user, 'creator')
        ->completed()
        ->create();

    livewire(CreateStockOpname::class)
        ->fillForm(['notes' => 'Opname baru'])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    expect(StockOpname::where('merchant_id', $this->merchant->id)->count())->toBe(2);
});

test('can create new opname after previous one is Canceled', function () {
    StockOpname::factory()
        ->for($this->merchant, 'merchant')
        ->for($this->user, 'creator')
        ->canceled()
        ->create();

    livewire(CreateStockOpname::class)
        ->fillForm(['notes' => 'Opname baru'])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    expect(StockOpname::where('merchant_id', $this->merchant->id)->count())->toBe(2);
});

test('open opname from another merchant does not block creation (tenant isolation)', function () {
    $otherMerchant = Merchant::factory()->active()->create();

    StockOpname::factory()
        ->for($otherMerchant, 'merchant')
        ->create(['status' => StockOpnameStatus::Counting]);

    livewire(CreateStockOpname::class)
        ->fillForm(['notes' => 'Merchant lain punya open'])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    expect(StockOpname::where('merchant_id', $this->merchant->id)->count())->toBe(1);
});
