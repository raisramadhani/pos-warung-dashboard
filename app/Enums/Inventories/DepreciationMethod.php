<?php

namespace App\Enums\Inventories;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

/**
 * Metode penyusutan aset.
 *
 * Rumus yang dipakai (lihat App\Services\AssetDepreciationService):
 * - NonDepreciable : aset tidak disusutkan (useful_life_months = null)
 * - StraightLine   : rate tahunan = 100% / umur tahun;
 *                    monthly = (acquisition_cost - salvage_value) / useful_life_months (beban tetap)
 * - ReduceBalance  : rate tahunan = 200% / umur tahun (double declining, dikunci);
 *                    monthly = book_value_before x (2 / useful_life_months) (beban menurun);
 *                    periode terakhir mengambil sisa nilai buku penuh => nilai buku 0
 */
enum DepreciationMethod: string implements HasColor, HasDescription, HasLabel
{
    case NonDepreciable = 'non_depreciable';
    case StraightLine = 'straight_line';
    case ReduceBalance = 'reduce_balance';

    public function getLabel(): string
    {
        return match ($this) {
            self::NonDepreciable => 'Non Depresiasi',
            self::StraightLine => 'Garis Lurus',
            self::ReduceBalance => 'Saldo Menurun',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::NonDepreciable => 'gray',
            self::StraightLine => 'success',
            self::ReduceBalance => 'warning',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::NonDepreciable => 'Aset tidak mengalami penyusutan nilai',
            self::StraightLine => 'Penyusutan dibagi rata selama masa manfaat',
            self::ReduceBalance => 'Penyusutan lebih besar di awal masa manfaat (double declining)',
        };
    }
}
