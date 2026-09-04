@extends('pos.layout')

@section('title', 'POS')
@section('body-class', 'relative')

@section('body')
    @include('pos.partials.sidebar')
    @include('pos.partials.header-index')

    @include('pos.views.index')

    @include('pos.views.checkout')

    @include('pos.views.history')

    {{-- ============ MODALS ============ --}}
    @include('pos.modals.stock-opname-lock')
    @include('pos.modals.clear-cart')
    @include('pos.modals.back-confirm')
    @include('pos.modals.sidebar-nav-confirm')
    @include('pos.modals.customer')
    @include('pos.modals.promo')
    @include('pos.modals.notes')
    @include('pos.modals.success')
    @include('pos.modals.history-detail')
    @include('pos.modals.bluetooth')

    {{-- ============ APP CONFIG (untuk resources/js/pos.js) ============ --}}
    @php
        $posRoutes = [
            'products' => route('pos.products'),
            'history' => route('pos.history'),
            'historyDetail' => route('pos.historyDetail', ['id' => '__ID__']),
            'preview' => route('pos.preview'),
            'process' => route('pos.process'),
        ];
    @endphp
    <script>
        window.POS_CONFIG = {
            isTransactionLocked: @json($isTransactionLocked),
            merchantName: @json($merchant['name'] ?? 'Toko'),
            merchantAddress: @json($merchant['address'] ?? ''),
            cashierName: @json(auth()->user()->name),
            csrfToken: @json(csrf_token()),
            routes: @json($posRoutes),
            products: @json($products),
            categories: @json($categories),
            promoBadges: @json($promoBadges),
            effectivePrices: @json($effectivePrices),
        };
    </script>

    @vite(['resources/js/pos.js'])
@endsection
