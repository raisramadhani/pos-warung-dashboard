<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentNumberService
{
    /**
     * Generate document number with format: {PREFIX}{YYMM}/{MERCHANT_ID}/{SEQ}
     *
     * Examples:
     * - GR-2607/000/001  (Goods Receipt, superadmin, sequence 001)
     * - PO-2607/001/005  (Purchase Order, merchant ID 1, sequence 005)
     *
     * Sequence resets monthly (per YYMM).
     */
    public function generate(
        string $prefix,
        ?int $merchantId,
        string $modelClass,
        string $field = 'receipt_number',
    ): string {
        $yy = now()->format('y');
        $mm = now()->format('m');
        $merchantCode = $merchantId
            ? str_pad((string) $merchantId, 3, '0', STR_PAD_LEFT)
            : '000';

        $pattern = "{$prefix}{$yy}{$mm}/{$merchantCode}/";

        $query = $modelClass::query();

        // Sertakan record yang soft-deleted agar nomor tidak bentrok dengan
        // baris yang masih menempati unique index (mis. GR/PO yang dihapus).
        if (\in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)) {
            $query->withTrashed();
        }

        /** @var Model|null $lastRecord */
        $lastRecord = $query->where($field, 'like', "{$pattern}%")
            ->orderBy('id', 'desc')
            ->lockForUpdate()
            ->first();

        if ($lastRecord) {
            $lastSeq = (int) substr($lastRecord->{$field}, -3);
            $seq = str_pad((string) ($lastSeq + 1), 3, '0', STR_PAD_LEFT);
        } else {
            $seq = '001';
        }

        return "{$pattern}{$seq}";
    }
}
