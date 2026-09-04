<?php

namespace App\Filament\Admin\Resources\Distributions\Pages;

use App\Filament\Admin\Resources\Distributions\DistributionResource;
use App\Models\Inventories\Distribution;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class EditDistribution extends EditRecord
{
    protected static string $resource = DistributionResource::class;

    protected ?string $heading = 'Edit Distribusi';

    protected ?string $subheading = 'Ubah data distribusi barang';

    public function form(Schema $schema): Schema
    {
        \assert($this->record instanceof Distribution);

        // Stok langsung terpengaruh saat status Dikirim (created),
        // sehingga data distribusi tidak boleh diedit lagi.
        return $schema->disabled();
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
        \assert($this->record instanceof Distribution);

        // Stok sudah terpengaruh saat create, edit tidak diperbolehkan.
        $this->halt();

        return $data;
    }

    protected function afterSave(): void
    {
        \assert($this->record instanceof Distribution);

        // Stok sudah terpengaruh saat create, edit tidak diperbolehkan.
        $this->halt();
    }
}
