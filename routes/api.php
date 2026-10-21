<?php

use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\WeatherController;
use Illuminate\Support\Facades\Route;

Route::apiResource('tasks', TaskController::class);

Route::get('/integrations/weather', [WeatherController::class, 'show'])
    ->middleware('throttle:30,1');
