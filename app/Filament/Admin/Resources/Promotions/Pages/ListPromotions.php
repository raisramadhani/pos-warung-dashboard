<?php

namespace App\Filament\Admin\Resources\Promotions\Pages;

use App\Filament\Admin\Resources\Promotions\PromotionResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListPromotions extends ListRecords
{
    protected static string $resource = PromotionResource::class;

    protected ?string $heading = 'Promo';

    protected ?string $subheading = 'Daftar promo milik setiap outlet';

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
}
