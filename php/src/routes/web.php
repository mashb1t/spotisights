<?php

use App\Http\Controllers\CallbackController;
use App\Http\Controllers\ConnectServiceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [ConnectServiceController::class, 'index'])->name('connect');

Route::get('/spotify/callback', [CallbackController::class, 'spotifyCallback']);
