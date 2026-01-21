<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PaysController;
use App\Http\Controllers\Api\MinistereController;
use App\Http\Controllers\Api\ProvinceController;
use App\Http\Controllers\Api\CommuneController;
use App\Http\Controllers\Api\ZoneController;
use App\Http\Controllers\Api\CollineController;

Route::apiResource('pays', PaysController::class);
Route::apiResource('ministeres', MinistereController::class);
Route::apiResource('provinces', ProvinceController::class);
Route::apiResource('communes', CommuneController::class);
Route::apiResource('zones', ZoneController::class);
Route::apiResource('collines', CollineController::class);
