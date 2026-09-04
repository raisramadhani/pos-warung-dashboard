<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Endpoint API ringan untuk POS. Response memakai JSON (sudah diatur via
| shouldRenderJsonWhen di bootstrap/app.php untuk path api/*).
|
*/

// Timestamp server (ms) untuk sinkronisasi jam POS.
// Header no-store mencegah proxy/browser menyimpan response sehingga klien
// selalu mendapat waktu terbaru (bukan dari cache).
Route::get('/server-time', function () {
    return response()
        ->json(['timestamp' => now()->valueOf()])
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
})->name('server-time');
