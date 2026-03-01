<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChargingStationController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\VehicleController;


Route::get('/', [MapController::class, 'index'])->name('map.index');
Route::get('/vehicles', [VehicleController::class, 'fetchVehicleList']);
Route::get('/vehicles/{id}', [VehicleController::class, 'fetchVehicleDetails'])->name('vehicles.show');