<?php

namespace App\Filament\Admin\Resources\StockMovements\Pages;

use App\Filament\Admin\Resources\StockMovements\StockMovementResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewStockMovement extends ViewRecord
{
    protected static string $resource = StockMovementResource::class;

    protected ?string $heading = 'Detail Riwayat Stok';

    protected ?string $subheading = 'Informasi lengkap pergerakan stok';

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
        return [];
    }
}
