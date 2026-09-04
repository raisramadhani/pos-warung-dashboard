<?php

namespace App\Filament\Merchant\Resources\Promotions\Pages;

use App\Filament\Merchant\Resources\Promotions\Actions\ActivatePromotionAction;
use App\Filament\Merchant\Resources\Promotions\Actions\DeactivatePromotionAction;
use App\Filament\Merchant\Resources\Promotions\PromotionResource;
use App\Filament\Merchant\Resources\Promotions\RelationManagers\RedemptionsRelationManager;
use App\Filament\Merchant\Resources\Promotions\Widgets\PromotionOverviewWidget;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewPromotion extends ViewRecord
{
    protected static string $resource = PromotionResource::class;

    protected ?string $heading = 'Detail Promo';

    protected ?string $subheading = 'Informasi lengkap promo';

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
            ActivatePromotionAction::make(),
            DeactivatePromotionAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PromotionOverviewWidget::class,
        ];
    }

    public function getRelationManagers(): array
    {
        return [
            RedemptionsRelationManager::class,
        ];
    }
}
