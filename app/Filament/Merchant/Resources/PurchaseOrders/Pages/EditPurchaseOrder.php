<?php

namespace App\Filament\Merchant\Resources\PurchaseOrders\Pages;

use App\Enums\Inventories\PurchaseOrderStatus;
use App\Filament\Merchant\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Filament\Merchant\Resources\PurchaseOrders\Schemas\PurchaseOrderForm;
use App\Models\Inventories\PurchaseOrder;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class EditPurchaseOrder extends EditRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected ?string $heading = 'Edit Purchase Order';

    protected ?string $subheading = 'Ubah data purchase order';

    public function form(Schema $schema): Schema
    {
        \assert($this->record instanceof PurchaseOrder);

        if ($this->record->status !== PurchaseOrderStatus::Approved) {
            return $schema->disabled();
        }

        return PurchaseOrderForm::configure($schema);
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
            Actions\ViewAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        \assert($this->record instanceof PurchaseOrder);

        if ($this->record->status !== PurchaseOrderStatus::Approved) {
            $this->halt();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        \assert($this->record instanceof PurchaseOrder);

        if ($this->record->status !== PurchaseOrderStatus::Approved) {
            $this->halt();
        }
    }
}
