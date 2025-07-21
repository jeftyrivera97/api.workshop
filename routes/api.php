<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\GastoController;
use App\Http\Controllers\IngresoController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EnsureTokenIsValid;


// Ruta pública para login
Route::post('/login', [UserController::class, 'login']);
Route::middleware('auth:sanctum')->get('/user/renew/token', [UserController::class, 'renewToken']);

Route::get('/empleado', [EmpleadoController::class, 'index'])->middleware(['auth:sanctum', EnsureTokenIsValid::class]);
Route::get('/compra', [CompraController::class, 'index'])->middleware(['auth:sanctum', EnsureTokenIsValid::class]);
Route::get('/ingreso', [IngresoController::class, 'index'])->middleware(['auth:sanctum', EnsureTokenIsValid::class]);
Route::get('/compra', [CompraController::class, 'index'])->middleware(['auth:sanctum', EnsureTokenIsValid::class]);
Route::get('/gasto', [GastoController::class, 'index'])->middleware(['auth:sanctum', EnsureTokenIsValid::class]);
