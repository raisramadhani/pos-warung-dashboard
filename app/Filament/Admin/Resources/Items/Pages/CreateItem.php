<?php

namespace App\Filament\Admin\Resources\Items\Pages;

use App\Enums\Inventories\ItemType;
use App\Enums\Inventories\StockMovementType;
use App\Filament\Admin\Resources\Items\ItemResource;
use App\Models\Inventories\Item;
use App\Models\Merchants\Merchant;
use App\Services\StockMovementService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateItem extends CreateRecord
{
    protected static string $resource = ItemResource::class;

    protected ?string $heading = 'Tambah Item';

    protected ?string $subheading = 'Buat item baru';

    /** Stok awal yang diisi user, diproses setelah item dibuat. */
    protected float $openingStock = 0;

    public function getHeading(): string|Htmlable|null
    {
        return $this->heading ?? $this->getTitle();
    }

    public function getSubheading(): string|Htmlable|null
    {
        return $this->subheading;
    }

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? (string) str(class_basename(static::class))
            ->kebab()
            ->replace('-', ' ')
            ->ucwords();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // opening_stock bukan kolom tabel items; ditangkap lalu dihapus agar
        // tidak masuk ke mass-assignment, kemudian diproses di afterCreate().
        $this->openingStock = (float) ($data['opening_stock'] ?? 0);
        unset($data['opening_stock']);

        return $data;
    }

    protected function afterCreate(): void
    {
        // Stok awal hanya untuk bahan baku (RawMaterial), Non Bahan Baku tidak terpengaruh.
        if (
            $this->openingStock <= 0
            || ! $this->record instanceof Item
            || $this->record->type !== ItemType::RawMaterial
        ) {
            return;
        }

        $warehouse = Merchant::warehouse();

        if (! $warehouse) {
            return;
        }

        app(StockMovementService::class)->increase(
            merchantId: $warehouse->getKey(),
            itemId: $this->record->getKey(),
            quantity: $this->openingStock,
            type: StockMovementType::Opening,
            reference: $this->record,
            notes: 'Stok awal saat item dibuat',
        );
    }
}
