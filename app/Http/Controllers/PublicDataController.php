<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Observation;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicDataController extends Controller
{
    // Return last observations and active alerts
    public function index(): JsonResponse
    {
        $observations = Observation::with('station')
            ->latest()
            ->take(5)
            ->get();

        $alerts = Alert::where('is_active', true)
            ->latest()
            ->get(['id', 'title', 'message', 'level', 'created_at']);

        return response()->json([
            'message' => 'Welcome to the Weather Observations API',
            'latest_observations' => $observations,
            'alerts' => $alerts,
        ]);
    }
}
