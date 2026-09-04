<?php

namespace App\Filament\Merchant\Resources\CashDrawerShifts\Pages;

use App\Filament\Merchant\Resources\CashDrawerShifts\CashDrawerShiftResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewCashDrawerShift extends ViewRecord
{
    protected static string $resource = CashDrawerShiftResource::class;

    protected ?string $heading = 'Detail Shift Kas';

    protected ?string $subheading = 'Rincian shift kas dan akumulasi cashdrawer';

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
