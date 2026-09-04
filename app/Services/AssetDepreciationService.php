<?php

namespace App\Services;

use App\Enums\Inventories\AssetStatus;
use App\Enums\Inventories\DepreciationMethod;
use App\Models\Inventories\Asset;
use App\Models\Inventories\AssetDepreciation;
use Illuminate\Support\Carbon;

class AssetDepreciationService
{
    /**
     * Menghitung persentase penyusutan tahunan (Nilai / Tahun dalam persen).
     *
     * Rumus (divisor selalu masa manfaat asli, BUKAN sisa umur):
     * - Straight Line   : rate = 1200 / useful_life_months  => setara 100% / umur tahun
     * - Reduce Balance  : rate = 2400 / useful_life_months  => double declining (200% / umur tahun)
     *
     * Aset non-depresiasi atau masa manfaat <= 0 mengembalikan 0.
     */
    public function annualRatePercent(Asset $asset): float
    {
        if ($asset->depreciation_method === DepreciationMethod::NonDepreciable || $asset->useful_life_months <= 0) {
            return 0;
        }

        $factor = $asset->depreciation_method === DepreciationMethod::ReduceBalance ? 2400 : 1200;

        return $factor / $asset->useful_life_months;
    }

    /**
     * Menghitung beban penyusutan untuk satu periode tertentu.
     * Delegasikan ke metode privat calculate() dan kembalikan hanya besarannya.
     * Dipakai untuk menampilkan proyeksi di halaman Jadwal Penyusutan.
     */
    public function monthlyAmountForPeriod(Asset $asset, Carbon $periodDate): float
    {
        return $this->calculate($asset, $periodDate)['amount'];
    }

    /**
     * Menentukan periode penyusutan berikutnya yang WAJIB disusutkan (paling tua belum tercatat).
     * Aturan wajib urut (sequential) untuk menjaga laporan keuangan tidak berlubang.
     *
     * Belum pernah disusutkan (last_depreciation_date null):
     * - Akuisisi pada bulan yang sama dengan aset masuk sistem (aset baru) => mulai BULAN DEPAN.
     * - Akuisisi lebih lama dari bulan aset masuk (aset lama/migrasi)     => mulai BULAN BERJALAN
     *   (tanpa backfill; akumulasi masa lalu sudah dijurnal terpisah di Saldo Awal Akuntansi).
     * Sudah pernah disusutkan => last_depreciation_date + 1 bulan (rantai ketat).
     *
     * Mengembalikan null bila periode berikutnya belum jatuh tempo (di atas bulan berjalan)
     * atau aset tidak memenuhi syarat disusutkan.
     *
     * @param  Carbon|null  $currentMonth  titik acuan "sekarang" (default: bulan berjalan)
     */
    public function nextPendingPeriod(Asset $asset, ?Carbon $currentMonth = null): ?Carbon
    {
        $currentMonth = ($currentMonth ?? now())->startOfMonth();

        if ($asset->depreciation_method === DepreciationMethod::NonDepreciable) {
            return null;
        }

        if ($asset->status !== AssetStatus::Active) {
            return null;
        }

        if ($asset->useful_life_months <= 0) {
            return null;
        }

        if ($asset->last_depreciation_date !== null) {
            $period = $asset->last_depreciation_date->copy()->addMonth()->startOfMonth();

            return $period->lte($currentMonth) ? $period : null;
        }

        $acquisitionMonth = $asset->acquisition_date->copy()->startOfMonth();
        $createdMonth = $asset->created_at->copy()->startOfMonth();

        if ($acquisitionMonth->equalTo($createdMonth)) {
            $period = $createdMonth->copy()->addMonth()->startOfMonth();
        } else {
            $period = $createdMonth->copy()->startOfMonth();
        }

        return $period->lte($currentMonth) ? $period : null;
    }

    /**
     * Menerapkan (apply) penyusutan untuk satu aset pada periode tertentu.
     *
     * Guard KETAT: periode hanya diterima bila persis sama dengan nextPendingPeriod()
     * (wajib urut). Jika admin mencoba menerapkan periode yang lebih baru sebelum periode
     * tertua tercatat, method ini menolak (return null).
     *
     * Alur:
     * 1. Lewati jika aset non-depresiasi, bukan Active, atau masa manfaat <= 0.
     * 2. Lewati jika periode bukan periode tertua yang belum disusutkan (anti lompat bulan).
     * 3. Lewati jika periode sudah pernah dicatat (unique asset_id + period_date).
     * 4. Hitung besarannya, simpan baris AssetDepreciation, lalu perbarui last_depreciation_date
     *    dan status (FullyDepreciated bila nilai buku mencapai nilai sisa).
     *
     * @return AssetDepreciation|null baris yang disimpan, atau null bila tidak ada penyusutan yang berlaku
     */
    public function apply(Asset $asset, Carbon $periodDate): ?AssetDepreciation
    {
        if ($asset->depreciation_method === DepreciationMethod::NonDepreciable) {
            return null;
        }

        if ($asset->status !== AssetStatus::Active) {
            return null;
        }

        if ($asset->useful_life_months <= 0) {
            return null;
        }

        $pending = $this->nextPendingPeriod($asset);

        if ($pending === null || ! $periodDate->equalTo($pending)) {
            return null;
        }

        if ($asset->depreciations()->where('period_date', $periodDate)->exists()) {
            return null;
        }

        $calculated = $this->calculate($asset, $periodDate);

        if ($calculated['amount'] <= 0) {
            return null;
        }

        $depreciation = AssetDepreciation::query()->create([
            'asset_id' => $asset->id,
            'period_date' => $periodDate,
            'depreciation_amount' => $calculated['amount'],
            'book_value_before' => $calculated['book_value_before'],
            'book_value_after' => $calculated['book_value_after'],
        ]);

        $asset->update([
            'last_depreciation_date' => $periodDate,
            'status' => $calculated['book_value_after'] <= $asset->salvage_value
                ? AssetStatus::FullyDepreciated
                : AssetStatus::Active,
        ]);

        return $depreciation;
    }

    /**
     * Menerapkan penyusutan untuk banyak aset sekaligus (massal) pada periode tertua
     * masing-masing. Tiap aset bisa memiliki periode tertunggak yang berbeda.
     *
     * @param  iterable<Asset>  $assets
     * @return int jumlah aset yang berhasil diterapkan
     */
    public function applyNext(iterable $assets, ?Carbon $currentMonth = null): int
    {
        $applied = 0;

        foreach ($assets as $asset) {
            $pending = $this->nextPendingPeriod($asset, $currentMonth);

            if ($pending !== null && $this->apply($asset, $pending) !== null) {
                $applied++;
            }
        }

        return $applied;
    }

    /**
     * Menerapkan penyusutan untuk banyak aset pada SATU periode yang sama.
     * Guard ketat tetap berlaku per aset (periode harus periode tertua aset tsb).
     *
     * @param  iterable<Asset>  $assets
     * @return int jumlah aset yang berhasil diterapkan
     */
    public function applyMany(iterable $assets, Carbon $periodDate): int
    {
        $applied = 0;

        foreach ($assets as $asset) {
            if ($this->apply($asset, $periodDate) !== null) {
                $applied++;
            }
        }

        return $applied;
    }

    /**
     * Inti perhitungan penyusutan untuk satu periode.
     *
     * Nilai buku sebelum:
     * - Sudah ada riwayat  : harga akuisisi - akumulasi dari tabel asset_depreciations.
     * - Belum ada riwayat  :
     *   - Straight Line : harga akuisisi penuh (beban tetap; sisa historis sudah dijurnal
     *     terpisah di Saldo Awal Akuntansi).
     *   - Reduce Balance: dibuka via simulasi in-memory openingBookValue() sehingga nilai
     *     buku awal akurat untuk aset lama tanpa riwayat di sistem.
     *
     * Besaran bulanan:
     * - Straight Line  : (acquisition_cost - salvage_value) / useful_life_months
     *     Divisor TETAP masa manfaat asli — beban konstan dari bulan pertama sampai terakhir.
     * - Reduce Balance : book_value_before x (2 / useful_life_months)
     *     Beban menurun; periode terakhir (appliedCount + 1 >= useful_life_months) mengambil
     *     sisa nilai buku penuh agar nilai buku tepat 0 di akhir masa manfaat.
     *
     * Besaran selalu dibatasi tidak melebihi sisa sebelum nilai sisa.
     *
     * @return array{amount: float, book_value_before: float, book_value_after: float}
     */
    private function calculate(Asset $asset, Carbon $periodDate): array
    {
        $months = $asset->useful_life_months;
        $salvage = (float) $asset->salvage_value;

        if ($asset->depreciation_method === DepreciationMethod::NonDepreciable || $months <= 0) {
            return [
                'amount' => 0,
                'book_value_before' => (float) $asset->acquisition_cost,
                'book_value_after' => (float) $asset->acquisition_cost,
            ];
        }

        $hasHistory = $asset->depreciations()->exists();

        if ($hasHistory) {
            $bookValueBefore = (float) $asset->acquisition_cost - (float) $asset->depreciations()->sum('depreciation_amount');
            $appliedCount = $asset->depreciations()->count();
        } elseif ($asset->depreciation_method === DepreciationMethod::ReduceBalance) {
            $bookValueBefore = $this->openingBookValue($asset, $periodDate);
            $appliedCount = max(0, $asset->acquisition_date->diffInMonths($periodDate) - 1);
        } else {
            $bookValueBefore = (float) $asset->acquisition_cost;
            $appliedCount = 0;
        }

        if ($bookValueBefore <= $salvage) {
            return [
                'amount' => 0,
                'book_value_before' => $bookValueBefore,
                'book_value_after' => $bookValueBefore,
            ];
        }

        $remainingBeforeSalvage = $bookValueBefore - $salvage;

        $amount = match ($asset->depreciation_method) {
            DepreciationMethod::ReduceBalance => $appliedCount + 1 >= $months
                ? $remainingBeforeSalvage
                : $bookValueBefore * (2 / $months),
            default => ($asset->acquisition_cost - $salvage) / $months,
        };

        $amount = max(0, min($amount, $remainingBeforeSalvage));

        return [
            'amount' => $amount,
            'book_value_before' => $bookValueBefore,
            'book_value_after' => $bookValueBefore - $amount,
        ];
    }

    /**
     * Membuka nilai buku awal untuk aset Reduce Balance yang BELUM memiliki riwayat
     * di tabel asset_depreciations (aset lama/migrasi).
     *
     * Simulasi penyusutan masa lalu dilakukan in-memory (tanpa query DB): nilai buku
     * dikurangi rate bulanan (2 / useful_life_months) sebanyak bulan yang seharusnya
     * sudah disusutkan sebelum periode ini (periode akuisisi tidak ikut disusutkan).
     *
     * Contoh: AC Rp 10jt, umur 60 bln, elapsed 23 bulan =>
     * bookValue = 10jt x (1 - 2/60)^23 ≈ Rp 4,72jt, dan beban bulan berjalan dihitung dari angka ini.
     */
    private function openingBookValue(Asset $asset, Carbon $periodDate): float
    {
        $rate = 2 / $asset->useful_life_months;
        $elapsed = max(0, $asset->acquisition_date->diffInMonths($periodDate) - 1);

        $bookValue = (float) $asset->acquisition_cost;

        for ($i = 0; $i < $elapsed; $i++) {
            $bookValue -= $bookValue * $rate;
        }

        return $bookValue;
    }
}
