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
        $yearAverageTemperature = $user->observations()
            ->where('observed_at', '>=', now()->subDays(365))
            ->avg('temperature');

        return response()->json([
            'stations' => $stations,
            'recent_observations' => $recentObservations,
            'stats' => [
                'total_assigned' => $stations->count(),
                'year_average_temperature' => round(floatval($yearAverageTemperature), 2),
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

        $user = Auth::user();

        $observations = Observation::where('station_id', $request->station_id)
            ->where('user_id', $user->id)
            ->whereBetween('observed_at', [$request->start_date, $request->end_date . ' 23:59:59'])
            ->orderBy('observed_at')
            ->get();

        if ($observations->isEmpty()) {
            return response()->json([
                'message' => 'No observations found for the specified criteria.',
            ], 404);
        }

        return response()->json([
            'message' => 'Report generated successfully.',
            'observer' => ['id' => $user->id, 'name' => $user->name],
            'filename' => 'reporte_' . $request->station_id . '.csv',
            'data' => $observations,
        ]);
    }
}
