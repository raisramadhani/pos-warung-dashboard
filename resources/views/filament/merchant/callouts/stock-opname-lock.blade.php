@php
    /** @var \App\Models\Inventories\StockOpname|null $activeOpname */
    $activeOpname = \App\Filament\Merchant\Resources\StockOpnames\StockOpnameResource::getActiveLockedOpname();
@endphp

@if($activeOpname)
    <div class="mb-6">
        <x-filament::callout
            icon="heroicon-o-exclamation-triangle"
            color="warning"
        >
            <x-slot name="heading">
                Transaksi Terkunci
            </x-slot>

            <x-slot name="description">
                Transaksi masuk dan keluar tidak dapat dilakukan sementara karena sedang ada stock opname aktif
                (<strong>{{ $activeOpname->opname_number }}</strong>).
                Selesaikan atau batalkan stock opname terlebih dahulu.
            </x-slot>

            <x-slot name="controls">
                <x-filament::button
                    tag="a"
                    :href="\App\Filament\Merchant\Resources\StockOpnames\StockOpnameResource::getUrl('view', ['record' => $activeOpname])"
                    size="xs"
                    color="warning"
                    icon="heroicon-o-arrow-top-right-on-square"
                >
                    Lihat Stock Opname
                </x-filament::button>
            </x-slot>
        </x-filament::callout>
    </div>
@endif
