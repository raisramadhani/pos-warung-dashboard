<?php

namespace App\Filament\Admin\Resources\Merchants\Pages;

use App\Filament\Admin\Resources\Merchants\MerchantResource;
use App\Filament\Admin\Resources\Merchants\RelationManagers\DistributionsRelationManager;
use App\Filament\Admin\Resources\Merchants\RelationManagers\MembersRelationManager;
use App\Filament\Admin\Resources\Merchants\RelationManagers\StatusesRelationManager;
use App\Filament\Admin\Resources\Merchants\RelationManagers\StocksRelationManager;
use App\Filament\Admin\Resources\Merchants\RelationManagers\TransactionsRelationManager;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewMerchant extends ViewRecord
{
    protected static string $resource = MerchantResource::class;

    protected ?string $heading = 'Detail Outlet';

    protected ?string $subheading = 'Informasi lengkap outlet';

    public function getRelationManagers(): array
    {
        return [
            MembersRelationManager::class,
            StatusesRelationManager::class,
            StocksRelationManager::class,
            DistributionsRelationManager::class,
            TransactionsRelationManager::class,
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
        return [
            Actions\EditAction::make(),
        ];
    }
}
