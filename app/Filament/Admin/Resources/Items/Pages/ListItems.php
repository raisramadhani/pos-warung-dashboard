<?php

namespace App\Filament\Admin\Resources\Items\Pages;

use App\Filament\Admin\Resources\Items\ItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListItems extends ListRecords
{
    protected static string $resource = ItemResource::class;

    protected ?string $heading = 'Item / Barang';

    protected ?string $subheading = 'Daftar master barang';

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
