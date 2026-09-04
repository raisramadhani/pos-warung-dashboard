<?php

namespace App\Filament\Admin\Resources\GoodsReceipts\Pages;

use App\Enums\Inventories\GoodsReceiptStatus;
use App\Filament\Admin\Resources\GoodsReceipts\Actions\VerifyReceiptAction;
use App\Filament\Admin\Resources\GoodsReceipts\GoodsReceiptResource;
use App\Filament\Admin\Resources\GoodsReceipts\Schemas\GoodsReceiptForm;
use App\Models\Inventories\GoodsReceipt;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class EditGoodsReceipt extends EditRecord
{
    protected static string $resource = GoodsReceiptResource::class;

    protected ?string $heading = 'Edit Penerimaan';

    protected ?string $subheading = 'Ubah data penerimaan barang';

    public function form(Schema $schema): Schema
    {
        \assert($this->record instanceof GoodsReceipt);

        if ($this->record->status === GoodsReceiptStatus::Verified) {
            return $schema->disabled();
        }

        return GoodsReceiptForm::configure($schema);
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
            VerifyReceiptAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn (): bool => $this->record instanceof GoodsReceipt && $this->record->status === GoodsReceiptStatus::Draft),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        \assert($this->record instanceof GoodsReceipt);

        if ($this->record->status === GoodsReceiptStatus::Verified) {
            $this->halt();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        \assert($this->record instanceof GoodsReceipt);

        if ($this->record->status === GoodsReceiptStatus::Verified) {
            $this->halt();
        }
    }
}
