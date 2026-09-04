<?php

namespace App\Filament\Admin\Resources\Assets\Pages;

use App\Filament\Admin\Resources\Assets\AssetResource;
use App\Filament\Admin\Resources\Assets\RelationManagers\DepreciationsRelationManager;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewAsset extends ViewRecord
{
    protected static string $resource = AssetResource::class;

    protected ?string $heading = 'Detail Aset';

    protected ?string $subheading = 'Informasi lengkap aset';

    public function getRelationManagers(): array
    {
        return [
            DepreciationsRelationManager::class,
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
