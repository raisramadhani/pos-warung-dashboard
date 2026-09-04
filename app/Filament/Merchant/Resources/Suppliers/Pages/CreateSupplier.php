<?php

namespace App\Filament\Merchant\Resources\Suppliers\Pages;

use App\Filament\Merchant\Resources\Suppliers\SupplierResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateSupplier extends CreateRecord
{
    protected static string $resource = SupplierResource::class;

    protected ?string $heading = 'Tambah Supplier';

    protected ?string $subheading = 'Buat supplier baru';

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
        $tenant = filament()->getTenant();

        if ($tenant === null) {
            return $data;
        }

        $data['merchant_id'] = $tenant->getKey();

        return $data;
    }
}
