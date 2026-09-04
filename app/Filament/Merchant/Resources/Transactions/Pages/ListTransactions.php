<?php

namespace App\Filament\Merchant\Resources\Transactions\Pages;

use App\Filament\Merchant\Resources\Transactions\TransactionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;

    protected ?string $heading = 'Transaksi';

    protected ?string $subheading = 'Riwayat transaksi penjualan';

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
            // Create transaksi dimatikan karena seharusnya lewat fitur POS Kasir, bukan lewat halaman ini. Halaman ini hanya untuk melihat riwayat transaksi.
            // CreateAction::make(),
        ];
    }
}
