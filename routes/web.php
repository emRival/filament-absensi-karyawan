<?php

use App\Livewire\Presensi;
use Illuminate\Support\Facades\Route;


Route::group(['middleware' => 'auth', 'prefix' => 'admin'], function () {
    Route::get('presensi', Presensi::class)->name('presensi');
});

Route::get('/', function () {
    return view('welcome');
});