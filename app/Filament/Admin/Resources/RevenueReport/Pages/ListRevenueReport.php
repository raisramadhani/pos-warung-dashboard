<?php

namespace App\Filament\Admin\Resources\RevenueReport\Pages;

use App\Filament\Admin\Resources\RevenueReport\RevenueReportResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListRevenueReport extends ListRecords
{
    protected static string $resource = RevenueReportResource::class;

    protected ?string $heading = 'Laporan Pendapatan';

    protected ?string $subheading = 'Laporan pendapatan per produk';

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
}
