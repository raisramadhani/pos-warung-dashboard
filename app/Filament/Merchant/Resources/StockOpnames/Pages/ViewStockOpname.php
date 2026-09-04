<?php

namespace App\Filament\Merchant\Resources\StockOpnames\Pages;

use App\Filament\Merchant\Resources\StockOpnames\Actions\CancelAction;
use App\Filament\Merchant\Resources\StockOpnames\Actions\CompleteAction;
use App\Filament\Merchant\Resources\StockOpnames\Actions\ReturnToCountingAction;
use App\Filament\Merchant\Resources\StockOpnames\Actions\ReturnToDraftAction;
use App\Filament\Merchant\Resources\StockOpnames\Actions\ReviewResultsAction;
use App\Filament\Merchant\Resources\StockOpnames\Actions\StartCountingAction;
use App\Filament\Merchant\Resources\StockOpnames\RelationManagers\ItemsRelationManager;
use App\Filament\Merchant\Resources\StockOpnames\StockOpnameResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewStockOpname extends ViewRecord
{
    protected static string $resource = StockOpnameResource::class;

    protected ?string $heading = 'Detail Stock Opname';

    protected ?string $subheading = 'Detail penghitungan stok fisik';

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
            // StartCountingAction::make(),
            // ReviewResultsAction::make(),
            // CompleteAction::make(),
            // CancelAction::make(),
            // ReturnToCountingAction::make(),
            // ReturnToDraftAction::make(),
        ];
    }

    public function getRelationManagers(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }
}
