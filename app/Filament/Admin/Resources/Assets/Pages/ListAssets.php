<?php

namespace App\Filament\Admin\Resources\Assets\Pages;

use App\Filament\Admin\Resources\Assets\AssetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListAssets extends ListRecords
{
    protected static string $resource = AssetResource::class;

    protected ?string $heading = 'Aset';

    protected ?string $subheading = 'Daftarkan aset yang hendak memiliki depresiasi';

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
            Actions\CreateAction::make(),
        ];
    }
}
