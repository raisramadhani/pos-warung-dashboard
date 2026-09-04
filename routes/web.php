<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ScheduleCalendarController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::middleware(['auth'])->group(function () {

    Route::get('/api/schedule-calendar', ScheduleCalendarController::class)
        ->name('api.schedule-calendar');

    Route::middleware(['can:access-pos'])
        ->prefix('/kasir')
        ->name('pos.')
        ->group(function () {
            Route::get('/', [PosController::class, 'index'])->name('index');
            Route::get('/products', [PosController::class, 'products'])->name('products');
            Route::get('/history', [PosController::class, 'history'])->name('history');
            Route::get('/history/{id}', [PosController::class, 'historyDetail'])->name('historyDetail');
            Route::post('/preview', [PosController::class, 'preview'])->name('preview');
            Route::post('/process', [PosController::class, 'process'])->name('process');
        });

});
