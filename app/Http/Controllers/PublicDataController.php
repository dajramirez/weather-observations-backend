<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Observation;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicDataController extends Controller
{
    /**
     * Return the 5 latest observations and active alerts.
     * This is the public endpoint (home).
     * 
     * @return JsonResponse
     */

    public function index(): JsonResponse
    {
        // Get the 5 latest observations with station info
        $observations = Observation::with('station')
            ->latest('observed_at')
            ->take(5)
            ->get();

        // Get all active alerts, including its station info
        $alerts = Alert::where('is_active', true)
            ->with('station')
            ->latest()
            ->get(['id', 'station_id', 'title', 'message', 'level', 'created_at']);

        return response()->json([
            'message' => 'Welcome to the Weather Observations API',
            'latest_observations' => $observations,
            'alerts' => $alerts,
        ]);
    }
}
