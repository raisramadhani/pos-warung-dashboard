<?php

use App\Enums\Inventories\DistributionStatus;
use App\Enums\Merchants\MerchantType;
use App\Filament\Admin\Resources\Distributions\Pages\CreateDistribution;
use App\Models\Inventories\Distribution;
use App\Models\Inventories\Item;
use App\Models\Inventories\MerchantStock;
use App\Models\Merchants\Merchant;
use App\Models\User;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->warehouse = Merchant::factory()->create(['type' => MerchantType::Warehouse, 'name' => 'Gudang Utama']);
    $this->merchant = Merchant::factory()->active()->create(['type' => MerchantType::Merchant, 'name' => 'Cabang Satu']);
    $this->item = Item::factory()->bahanBaku()->create();
    MerchantStock::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'item_id' => $this->item->id,
        'quantity' => 50,
    ]);
});

// ─── Happy Path ─────────────────────────────────────────

test('can render create page', function () {
    livewire(CreateDistribution::class)
        ->assertSuccessful();
});

test('can create a distribution and stock decreases', function () {
    livewire(CreateDistribution::class)
        ->fillForm([
            'source_merchant_id' => $this->warehouse->id,
            'merchant_id' => $this->merchant->id,
            'items' => [
                ['item_id' => $this->item->id, 'quantity_sent' => 10],
            ],
        ])
        ->call('create')
        ->assertNotified()
        ->assertHasNoFormErrors()
        ->assertRedirect();

    assertDatabaseHas('distributions', [
        'merchant_id' => $this->merchant->id,
        'source_merchant_id' => $this->warehouse->id,
        'status' => DistributionStatus::Sent->value,
    ]);

    expect((float) MerchantStock::where('merchant_id', $this->warehouse->id)->where('item_id', $this->item->id)->first()->quantity)->toBe(40.0);
});

test('can create distribution with multiple items', function () {
    $item2 = Item::factory()->bahanBaku()->create();
    MerchantStock::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'item_id' => $item2->id,
        'quantity' => 30,
    ]);

    livewire(CreateDistribution::class)
        ->fillForm([
            'source_merchant_id' => $this->warehouse->id,
            'merchant_id' => $this->merchant->id,
            'items' => [
                ['item_id' => $this->item->id, 'quantity_sent' => 5],
                ['item_id' => $item2->id, 'quantity_sent' => 10],
            ],
        ])
        ->call('create')
        ->assertNotified()
        ->assertHasNoFormErrors();

    expect((float) MerchantStock::where('merchant_id', $this->warehouse->id)->where('item_id', $this->item->id)->first()->quantity)->toBe(45.0);
    expect((float) MerchantStock::where('merchant_id', $this->warehouse->id)->where('item_id', $item2->id)->first()->quantity)->toBe(20.0);
});

test('creates distribution items with quantity_received default zero', function () {
    livewire(CreateDistribution::class)
        ->fillForm([
            'source_merchant_id' => $this->warehouse->id,
            'merchant_id' => $this->merchant->id,
            'items' => [
                ['item_id' => $this->item->id, 'quantity_sent' => 7],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $distribution = Distribution::where('merchant_id', $this->merchant->id)->first();

    expect($distribution->items)->toHaveCount(1)
        ->and((float) $distribution->items->first()->quantity_received)->toBe(0.0)
        ->and((float) $distribution->items->first()->quantity_sent)->toBe(7.0);
});

test('sends notification to merchant users on distribution', function () {
    $merchantUser = User::factory()->create();
    $this->merchant->members()->attach($merchantUser);

    livewire(CreateDistribution::class)
        ->fillForm([
            'source_merchant_id' => $this->warehouse->id,
            'merchant_id' => $this->merchant->id,
            'items' => [['item_id' => $this->item->id, 'quantity_sent' => 5]],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect($merchantUser->notifications)->toHaveCount(1)
        ->and($merchantUser->notifications->first()->data['title'])->toContain('Dikirim');
});

test('does not notify merchant without members', function () {
    livewire(CreateDistribution::class)
        ->fillForm([
            'source_merchant_id' => $this->warehouse->id,
            'merchant_id' => $this->merchant->id,
            'items' => [['item_id' => $this->item->id, 'quantity_sent' => 5]],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(DB::table('notifications')->count())->toBe(0);
});

// ─── Sad Path ───────────────────────────────────────────

test('validates the create form', function (array $data, array $errors) {
    $undo = Repeater::fake();

    foreach ($data['items'] as $i => $item) {
        if (($item['item_id'] ?? null) === 'ITEM_PLACEHOLDER') {
            $data['items'][$i]['item_id'] = $this->item->id;
        }
    }

    livewire(CreateDistribution::class)
        ->fillForm($data)
        ->call('create')
        ->assertHasFormErrors($errors)
        ->assertNotNotified()
        ->assertNoRedirect();

    $undo();
})->with([
    '`merchant_id` is required' => [
        ['source_merchant_id' => 999998, 'items' => [['item_id' => 'ITEM_PLACEHOLDER', 'quantity_sent' => 1]]],
        ['merchant_id' => 'required'],
    ],
    '`items` must have at least 1 item' => [
        ['source_merchant_id' => 999998, 'merchant_id' => 999999, 'items' => []],
        ['items' => 'required'],
    ],
    '`items.0.item_id` is required' => [
        ['source_merchant_id' => 999998, 'merchant_id' => 999999, 'items' => [['quantity_sent' => 1]]],
        ['items.0.item_id' => 'required'],
    ],
]);

// ─── Edge Cases ─────────────────────────────────────────

test('defaults source warehouse when not provided', function () {
    livewire(CreateDistribution::class)
        ->fillForm([
            'merchant_id' => $this->merchant->id,
            'items' => [['item_id' => $this->item->id, 'quantity_sent' => 3]],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $distribution = Distribution::where('merchant_id', $this->merchant->id)->first();

    expect($distribution->source_merchant_id)->toBe($this->warehouse->id);
});

test('configures form fields correctly', function () {
    livewire(CreateDistribution::class)
        ->assertSchemaComponentExists('source_merchant_id', checkComponentUsing: function (Select $field): bool {
            return $field->isRequired() && $field->isSearchable();
        })
        ->assertSchemaComponentExists('merchant_id', checkComponentUsing: function (Select $field): bool {
            return $field->isRequired() && $field->isSearchable();
        })
        ->assertSchemaComponentExists('items', checkComponentUsing: function (Repeater $field): bool {
            return $field->isRequired() && $field->getMinItems() === 1;
        });
});

test('status is not present on create form', function () {
    livewire(CreateDistribution::class)
        ->assertSchemaComponentDoesNotExist('status');
});
