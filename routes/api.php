<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SubsController;
use App\Http\Controllers\WeeksController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ResAdminController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('posts', PostController::class);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::apiResource('subs', SubsController::class);
Route::apiResource('weeks', WeeksController::class);
Route::apiResource('menus', MenuController::class);
Route::apiResource('reservations', ReservationController::class);
Route::apiResource('res-admin', ResAdminController::class);
Route::post('/reservations/{reservation}/confirm', [ReservationController::class, 'confirm']);
Route::post('/res-admin/{reservation}/complete', [ResAdminController::class, 'complete']);

