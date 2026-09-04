<?php

namespace App\Filament\Merchant\Resources\CashDrawerShifts\Pages;

use App\Filament\Merchant\Resources\CashDrawerShifts\Actions\CloseShiftAction;
use App\Filament\Merchant\Resources\CashDrawerShifts\Actions\OpenShiftAction;
use App\Filament\Merchant\Resources\CashDrawerShifts\CashDrawerShiftResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListCashDrawerShifts extends ListRecords
{
    protected static string $resource = CashDrawerShiftResource::class;

    protected ?string $heading = 'Shift Kas';

    protected ?string $subheading = 'Kelola pembukaan dan penutupan shift kasir';

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
            OpenShiftAction::make(),
            CloseShiftAction::make(),
        ];
    }
}
