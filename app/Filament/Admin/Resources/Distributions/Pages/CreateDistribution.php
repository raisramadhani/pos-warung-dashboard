<?php

namespace App\Filament\Admin\Resources\Distributions\Pages;

use App\Filament\Admin\Resources\Distributions\DistributionResource;
use App\Models\Inventories\Distribution;
use App\Models\Merchants\Merchant;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateDistribution extends CreateRecord
{
    protected static string $resource = DistributionResource::class;

    protected ?string $heading = 'Tambah Distribusi';

    protected ?string $subheading = 'Kirim barang ke cabang';

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
        $data['status'] = 'sent';
        $data['sent_at'] = now();

        if (empty($data['source_merchant_id'])) {
            $data['source_merchant_id'] = Merchant::warehouse()?->id;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Distribution $record */
        $record = $this->record;

        $record->loadMissing('merchant', 'items');

        $merchant = $record->merchant;
        $merchant->loadMissing('members');

        foreach ($merchant->members as $user) {
            Notification::make()
                ->title('Barang Dikirim')
                ->body("Terdapat {$record->items()->count()} jenis barang dikirim ke {$merchant->name}. Silakan konfirmasi penerimaan.")
                ->icon('tabler-truck-loading')
                ->sendToDatabase($user);
        }
    }
}
