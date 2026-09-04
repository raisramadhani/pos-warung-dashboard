<?php

namespace App\Filament\Merchant\Resources\Customers\Pages;

use App\Filament\Merchant\Resources\Customers\CustomerResource;
use App\Filament\Merchant\Resources\Customers\RelationManagers\TransactionsRelationManager;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected ?string $heading = 'Detail Pelanggan';

    protected ?string $subheading = 'Informasi lengkap pelanggan';

    public function getRecord(): Model
    {
        return parent::getRecord()->load('transactions');
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

    public function getRelationManagers(): array
    {
        return [
            TransactionsRelationManager::class,
        ];
    }
}
