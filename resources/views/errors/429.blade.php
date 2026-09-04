@extends('errors::minimal')

@section('title', __('Terlalu Banyak Permintaan'))
@section('code', '429')
@section('message', __('Anda telah mengirim terlalu banyak permintaan. Coba lagi nanti.'))
