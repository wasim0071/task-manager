<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\WeatherRequest;
use App\Services\WeatherService;
use Illuminate\Http\JsonResponse;

class WeatherController extends Controller
{
    public function show(WeatherRequest $request, WeatherService $weather): JsonResponse
    {
        return response()->json([
            'data' => $weather->lookup($request->string('city')->toString()),
        ]);
    }
}
