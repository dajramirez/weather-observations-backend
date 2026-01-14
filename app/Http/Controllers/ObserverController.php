<?php

namespace App\Http\Controllers;

use App\Models\Observation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class ObserverController extends Controller
{
    /**
     * Dashboard for the observer.
     * Only display stations assigned to the observer and their recent observations.
     */

    public function dashboard(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        // Obtain stations assigned to the observer
        $stations = $user->stations()->withCount('observations')->get();

        // 10 last observations made by the observer
        $recentObservations = $user->observations()
            ->with('station:id,name')
            ->latest('observed_at')
            ->take(10)
            ->get();

        // Calculate some simple statistics (e.g., today average temperature)
        $todayAvgTemp = $user->observations()
            ->whereDate('observed_at', today())
            ->avg('temperature');

        return response()->json([
            'stations' => $stations,
            'recent_observations' => $recentObservations,
            'stats' => [
                'total_assigned' => $stations->count(),
                'average_temperature_today' => round($todayAvgTemp ?? 0, 2),
            ]
        ]);
    }

    /**
     * Reports generation (CSV SIMULATION).
     */

    public function generateReport(Request $request): JsonResponse
    {
        $request->validate([
            'station_id' => 'required|exists:stations,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $observations = Observation::where('station_id', $request->station_id)
            ->whereBetween('observed_at', [$request->start_date, $request->end_date])
            ->get();

        if ($observations->isEmpty()) {
            return response()->json([
                'message' => 'No observations found for the specified criteria.',
            ], 404);
        }

        return response()->json([
            'message' => 'Report generated successfully.',
            'filename' => 'reporte_' . $request->station_id . '.csv',
            'data' => $observations,
        ]);
    }
}
