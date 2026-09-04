<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class BonusTiersData extends Data
{
    /**
     * @param  int  $step  Jumlah cup per kelipatan
     * @param  int  $amount  Bonus per kelipatan
     */
    public function __construct(
        public int $step,
        public int $amount,
    ) {}
}
