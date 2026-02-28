<?php

use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/info/server',   [App\Http\Controllers\InfoController::class, 'serverInfo']);
Route::get('/info/client',   [App\Http\Controllers\InfoController::class, 'clientInfo']);
Route::get('/info/database', [App\Http\Controllers\InfoController::class, 'databaseInfo']);