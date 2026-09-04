<?php

namespace App\Filament\Merchant\Resources\Transactions\Pages;

use App\Filament\Merchant\Resources\StockOpnames\StockOpnameResource;
use App\Filament\Merchant\Resources\Transactions\TransactionResource;
use App\Models\Products\Product;
use App\Models\Transactions\Transaction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;

    protected ?string $heading = 'Transaksi Baru';

    protected ?string $subheading = 'Buat transaksi penjualan';

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

        if (StockOpnameResource::isTransactionLocked()) {
            Notification::make()
                ->warning()
                ->title('Transaksi dikunci')
                ->body('Transaksi tidak dapat dibuat karena sedang ada stock opname aktif.')
                ->send();

            $this->redirect(
                StockOpnameResource::getUrl('index'),
                navigate: true,
            );
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['merchant_id'] = filament()->getTenant()?->getKey();
        // transaction_number auto-set by TransactionObserver::creating

        if (! empty($data['transactionItems'])) {
            $productIds = collect($data['transactionItems'])->pluck('product_id');
            $products = Product::query()->with(['category', 'productMaterials.item'])
                ->whereIn('id', $productIds)
                ->get()
                ->keyBy('id');

            foreach ($data['transactionItems'] as $idx => $item) {
                $product = $products->get($item['product_id'] ?? null);
                if ($product) {
                    $data['transactionItems'][$idx]['product_data'] = $product->toArray();
                }
            }
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Model {
            return parent::handleRecordCreation($data);
        });
    }

    protected function afterCreate(): void
    {
        \assert($this->record instanceof Transaction);

        $record = $this->record;

        /** @var Transaction $record */
        $subtotal = $record->transactionItems()->sum('subtotal');
        $total = max(0, $subtotal - (int) $record->discount);
        $amountReceived = (int) $record->amount_received;

        $record->update([
            'subtotal' => $subtotal,
            'total_amount' => $total,
            'change' => max(0, $amountReceived - $total),
        ]);
    }
}
