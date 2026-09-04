<?php

namespace App\Filament\Admin\Resources\Assets\Pages;

use App\Enums\Inventories\DepreciationMethod;
use App\Filament\Admin\Resources\Assets\AssetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditAsset extends EditRecord
{
    protected static string $resource = AssetResource::class;

    protected ?string $heading = 'Edit Aset';

    protected ?string $subheading = 'Ubah data aset';

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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['is_non_depreciable'] ?? false) === true) {
            $data['depreciation_method'] = DepreciationMethod::NonDepreciable->value;
            $data['useful_life_months'] = null;
        } else {
            $value = (float) ($data['useful_life_value'] ?? 0);
            $data['useful_life_months'] = $value > 0
                ? (($data['useful_life_unit'] ?? 'tahun') === 'bulan' ? max(1, (int) round($value)) : max(1, (int) round($value * 12)))
                : null;
        }

        unset(
            $data['is_non_depreciable'],
            $data['useful_life_unit'],
            $data['useful_life_value'],
        );

        return $data;
    }
}
