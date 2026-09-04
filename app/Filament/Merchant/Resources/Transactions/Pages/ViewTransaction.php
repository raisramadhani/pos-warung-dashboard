<?php

namespace App\Filament\Merchant\Resources\Transactions\Pages;

use App\Filament\Merchant\Resources\Transactions\RelationManagers\StockMovementsRelationManager;
use App\Filament\Merchant\Resources\Transactions\RelationManagers\TransactionItemsRelationManager;
use App\Filament\Merchant\Resources\Transactions\TransactionResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewTransaction extends ViewRecord
{
    protected static string $resource = TransactionResource::class;

    protected ?string $heading = 'Detail Transaksi';

    protected ?string $subheading = 'Informasi lengkap transaksi penjualan';

    public function getRelationManagers(): array
    {
        return [
            TransactionItemsRelationManager::class,
            StockMovementsRelationManager::class,
        ];
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
        return [];
    }
}
