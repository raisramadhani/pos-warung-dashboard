<?php

namespace App\Filament\Merchant\Resources\StockOpnames\Pages;

use App\Filament\Merchant\Resources\StockOpnames\StockOpnameResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListStockOpnames extends ListRecords
{
    protected static string $resource = StockOpnameResource::class;

    protected ?string $heading = 'Stock Opname';

    protected ?string $subheading = 'Penghitungan stok fisik outlet';

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
            CreateAction::make()
                ->label('Buat Stock Opname')
                ->icon('heroicon-o-plus')
                ->visible(fn () => ! StockOpnameResource::hasOpenOpname()),
        ];
    }
}
