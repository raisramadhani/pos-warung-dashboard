<?php

namespace App\Filament\Merchant\Resources\StockOpnames\Pages;

use App\Enums\Inventories\StockOpnameStatus;
use App\Filament\Merchant\Resources\StockOpnames\StockOpnameResource;
use App\Models\Inventories\MerchantStock;
use App\Models\Inventories\StockOpname;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class CreateStockOpname extends CreateRecord
{
    protected static string $resource = StockOpnameResource::class;

    protected ?string $heading = 'Buat Stock Opname';

    protected ?string $subheading = 'Pilih item yang akan dihitung stok fisiknya';

    protected bool $opnameAllItems = false;

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

    public function mount(): void
    {
        parent::mount();

        if (StockOpnameResource::hasOpenOpname()) {
            Notification::make()
                ->warning()
                ->title('Tidak bisa membuat stock opname baru')
                ->body('Masih ada stock opname yang belum ditutup. Selesaikan atau batalkan terlebih dahulu.')
                ->send();

            $this->redirect(
                StockOpnameResource::getUrl('index'),
                navigate: true,
            );
        }
    }

    protected function beforeCreate(): void
    {
        if (StockOpnameResource::hasOpenOpname()) {
            Notification::make()
                ->warning()
                ->title('Tidak bisa membuat stock opname baru')
                ->body('Masih ada stock opname yang belum ditutup.')
                ->send();

            $this->halt();
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['merchant_id'] = filament()->getTenant()?->getKey();
        $data['created_by'] = Auth::id();

        $this->opnameAllItems = (bool) ($data['opname_all_items'] ?? false);
        unset($data['opname_all_items']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->opnameAllItems) {
            /** @var StockOpname $record */
            $record = $this->record;

            $itemIds = MerchantStock::query()->where('merchant_id', $record->merchant_id)
                ->pluck('item_id');

            $items = $itemIds->map(fn (int $itemId): array => [
                'item_id' => $itemId,
                'system_quantity' => 0,
            ])->toArray();

            $record->items()->createMany($items);
        }

        // Automatically transition to Counting status to snapshot system quantities
        $this->record->update(['status' => StockOpnameStatus::Counting]);
    }
}
