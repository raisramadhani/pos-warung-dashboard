<?php

namespace App\Filament\Merchant\Resources\Categories\Pages;

use App\Filament\Merchant\Resources\Categories\CategoryResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;

    protected ?string $heading = 'Tambah Kategori';

    protected ?string $subheading = 'Buat kategori baru';

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
