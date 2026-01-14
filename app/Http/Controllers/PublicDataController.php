<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Observation;
use App\Models\Station;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicDataController extends Controller
{
    /**
     * Retrieve a list of all weather stations with their basic info.
     * 
     * @return JsonResponse
     */

    public function stations(): JsonResponse
    {
        $stations = Station::select('id', 'name', 'location', 'altitude', 'description')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $stations,
        ]);
    }

    /**
     * Retrieve the latest observations for a specific station.
     * 
     * @param int $stationId
     * @return JsonResponse
     */

    public function latestObservations(int $stationId): JsonResponse
    {
        $observations = Observation::where('station_id', $stationId)
            ->latest('observed_at')
            ->take(10)
            ->get();

        if ($observations->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No observations found for the specified station.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $observations,
        ]);
    }

    /**
     * Search for stations based on their name, location or description.
     * 
     * @param Request $request
     * @return JsonResponse
     */

    public function search(Request $request): JsonResponse
    {
        $query = $request->input('query');

        $results = Station::where('name', 'LIKE', "%{$query}%")
            ->orWhere('location', 'LIKE', "%{$query}%")
            ->orWhere('description', 'LIKE', "%{$query}%")
            ->get();

        return response()->json([
            'status' => 'success',
            'count' => $results->count(),
            'data' => $results,
        ]);
    }
}
