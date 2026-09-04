<?php

namespace App\Filament\Merchant\Resources\Suppliers\Pages;

use App\Filament\Merchant\Resources\Suppliers\SupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class EditSupplier extends EditRecord
{
    protected static string $resource = SupplierResource::class;

    protected ?string $heading = 'Edit Supplier';

    protected ?string $subheading = 'Ubah data supplier';

    public function form(Schema $schema): Schema
    {
        return SupplierResource::form($schema);
    }

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
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
