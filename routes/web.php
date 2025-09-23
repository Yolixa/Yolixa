<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebController;

Route::get('/', [WebController::class, 'index'])->name('index');
Route::get('/whitepaper', [WebController::class, 'whitepaper'])->name('whitepaper');
