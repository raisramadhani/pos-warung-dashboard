<?php

namespace App\Filament\Admin\Resources\Distributions\Pages;

use App\Filament\Admin\Resources\Distributions\DistributionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListDistributions extends ListRecords
{
    protected static string $resource = DistributionResource::class;

    protected ?string $heading = 'Distribusi Barang';

    protected ?string $subheading = 'Daftar pengiriman barang ke cabang';

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

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
