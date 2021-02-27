<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/login', [App\Http\Controllers\Api\ApiLoginController::class, 'login'])->name('apiCallback');
Route::get('/callback', [App\Http\Controllers\Api\ApiLoginController::class, 'callback'])->name('apiLogin');
