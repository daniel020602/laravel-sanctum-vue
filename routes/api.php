<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
//use App\Http\Controllers\PostController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ResAdminController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\TablesController;
use App\Http\Controllers\WeeksController;
use App\Http\Controllers\SubscriptionsController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//Route::apiResource('posts', PostController::class);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::put('/change-data', [AuthController::class, 'changeData'])->middleware('auth:sanctum');

// Admin user management
Route::get('/auth/list-users', [AuthController::class, 'listUsers'])->middleware('auth:sanctum');
Route::get('/auth/show-user/{id}', [AuthController::class, 'showUser'])->middleware('auth:sanctum');
Route::put('/auth/change-data/{id}', [AuthController::class, 'changeData'])->middleware('auth:sanctum');
Route::delete('/auth/delete-user/{id}', [AuthController::class, 'deleteUser'])->middleware('auth:sanctum');
Route::post('/auth/promote/{id}', [AuthController::class, 'promoteUser'])->middleware('auth:sanctum');
Route::post('/auth/demote/{id}', [AuthController::class, 'demoteUser'])->middleware('auth:sanctum');
Route::get('/auth/search-user', [AuthController::class, 'searchUser'])->middleware('auth:sanctum');

Route::apiResource('menus', MenuController::class);

Route::apiResource('reservations', ReservationController::class);
Route::post('/reservations/{reservation}/confirm', [ReservationController::class, 'confirm']);

Route::get('/res-admin/unconfirmed-count', [ResAdminController::class, 'countUnconfirmed'])->middleware('auth:sanctum');
Route::delete('/res-admin/delete-unconfirmed-reservations', [ResAdminController::class, 'deleteUnconfirmedReservations'])->middleware('auth:sanctum');
Route::apiResource('res-admin', ResAdminController::class);
Route::post('/res-admin/{reservation}/complete', [ResAdminController::class, 'complete']);

Route::get('/orders/current-order', [OrderController::class, 'userOrders'])->middleware('auth:sanctum');
Route::post('/orders/{order}/status', [OrderController::class, 'status'])->middleware('auth:sanctum');
Route::apiResource('orders', OrderController::class);
Route::post('/orders/{order}/pay', [OrderController::class, 'pay']);

Route::apiResource('tables', TablesController::class);

Route::get('weeks/next-week', [WeeksController::class, 'nextWeek']);
Route::apiResource('weeks', WeeksController::class);

Route::get('subscriptions/user-week', [SubscriptionsController::class, 'userWeek'])->middleware('auth:sanctum');
Route::apiResource('subscriptions', SubscriptionsController::class);
// admin weekly summary
Route::get('subscriptions/weekly-summary/{week}', [SubscriptionsController::class, 'weeklySummary'])->middleware('auth:sanctum');
