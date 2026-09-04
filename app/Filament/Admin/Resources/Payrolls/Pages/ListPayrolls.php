<?php

namespace App\Filament\Admin\Resources\Payrolls\Pages;

use App\Filament\Admin\Resources\Payrolls\PayrollResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListPayrolls extends ListRecords
{
    protected static string $resource = PayrollResource::class;

    protected ?string $heading = 'Penggajian';

    protected ?string $subheading = 'Daftar slip gaji karyawan outlet';

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
            Actions\CreateAction::make()
                ->label('Buat Gaji'),
            Actions\Action::make('bulkCreate')
                ->label('Buat Gaji Massal')
                ->icon('heroicon-o-user-group')
                ->color('success')
                ->url(PayrollResource::getUrl('bulk-create')),
        ];
    }
}
