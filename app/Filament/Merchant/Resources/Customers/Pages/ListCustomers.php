<?php

namespace App\Filament\Merchant\Resources\Customers\Pages;

use App\Filament\Merchant\Resources\Customers\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected ?string $heading = 'Pelanggan';

    protected ?string $subheading = 'Daftar pelanggan untuk merchant ini';

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
