<?php

namespace App\Filament\Admin\Resources\Merchants\Pages;

use App\Enums\Merchants\MerchantStatus;
use App\Filament\Admin\Resources\Merchants\MerchantResource;
use App\Models\Merchants\Merchant;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditMerchant extends EditRecord
{
    protected static string $resource = MerchantResource::class;

    protected ?string $heading = 'Edit Outlet';

    protected ?string $subheading = 'Ubah data Outlet';

    protected ?string $originalStatus = null;

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
            Actions\DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $record = $this->record;

        if ($record instanceof Merchant) {
            $original = $record->getOriginal('current_status');

            $this->originalStatus = $original instanceof MerchantStatus
                ? $original->value
                : $original;
        }
    }

    protected function afterSave(): void
    {
        $record = $this->record;

        if (! $record instanceof Merchant) {
            return;
        }

        $currentStatus = $record->current_status;

        if ($currentStatus->value !== $this->originalStatus) {
            $record->setStatus($currentStatus);
        }
    }
}
