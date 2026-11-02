<?php

namespace App\Http\Controllers;

use App\Services\AttendanceReverseGeocoder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceReverseGeocodeController extends Controller
{
    public function __invoke(Request $request, AttendanceReverseGeocoder $geocoder): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        return response()->json($geocoder->resolve(
            (float) $validated['latitude'],
            (float) $validated['longitude'],
        ));
    }
}
