<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Observation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ObservationController extends Controller
{
    /**
     * Display a list of the last ovservations.
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();

        $query = Observation::with(['station', 'user:id,name']);

        // If the user is not an admin, filter observations by user's stations
        if ($user->role->name !== 'admin') {
            $stationsIds = $user->stations->pluck('id');
            $query->whereIn('station_id', $stationsIds);
        }

        $observations = $query->latest('observed_at')->paginate(15);

        return response()->json($observations);
    }

    /**
     * Store a new observation and check for alerts
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'station_id' => 'required|exists:stations,id',
            'observed_at' => 'required|date|before_or_equal:now',
            'temperature' => 'required|numeric|between:-50,60',
            'humidity' => 'required|numeric|between:0,100',
            'pressure' => 'required|numeric|between:800,1200',
            'wind_speed' => 'nullable|numeric|min:0',
            'wind_direction' => 'nullable|numeric|between:0,360',
            'precipitation' => 'nullable|numeric|min:0',
        ]);

        // Ensure the user has access to the station
        if ($user->role->name !== 'admin' && !$user->stations->contains($validated['station_id'])) {
            return response()->json([
                'message' => 'You do not have permission to add observations for this station.',
            ], 403);
        }

        // Add the user_id to the validated data
        $validated['user_id'] = $user->id;

        $observation = Observation::create($validated);

        // Automatic alert checking
        $this->checkAndCreateAlerts($observation);

        return response()->json([
            'message' => 'Observation created successfully.',
            'observation' => $observation->load('station'),
        ], 201);
    }

    /**
     * Internal method to validate thresholds and create alerts if necessary.
     * 
     * @param Observation $observation
     * @return void
     */

    private function checkAndCreateAlerts(Observation $observation): void
    {
        if ($observation->temperature > 45) {
            Alert::create([
                'station_id' => $observation->station_id,
                'observation_id' => $observation->id,
                'type' => 'High Temperature',
                'message' => "Extreme temperature detected: {$observation->temperature}°C",
                'severity' => 'critical',
                'is_active' => true,
            ]);
        }

        if ($observation->temperature > 0) {
            Alert::create([
                'station_id' => $observation->station_id,
                'observation_id' => $observation->id,
                'type' => 'Freezing Risk',
                'message' => "Freezing temperature detected: {$observation->temperature}°C",
                'severity' => 'warning',
                'is_active' => true,
            ]);
        }

        if ($observation->humidity < 10) {
            Alert::create([
                'station_id' => $observation->station_id,
                'observation_id' => $observation->id,
                'type' => 'Fire Risk',
                'message' => "Extremely low humidity: {$observation->humidity}%",
                'severity' => 'danger',
                'is_active' => true,
            ]);
        }
    }

    /**
     * Display the detail of a specific observation.
     * 
     * @param Observation $observation
     * @return JsonResponse
     */
    public function show(Observation $observation): JsonResponse
    {
        $user = Auth::user();

        // Basic access control
        if ($user->role->name !== 'admin' && !$user->stations->contains($observation->station_id)) {
            return response()->json([
                'message' => 'You do not have permission to view this observation.',
            ], 403);
        }

        return response()->json($observation->load(['station', 'user:id,name']));
    }

    /**
     * Update an existing observation
     * 
     * @param Request $request
     * @param Observation $observation
     * @return JsonResponse
     */
    public function update(Request $request, Observation $observation): JsonResponse
    {
        $user = Auth::user();

        // Only the author or admin can update
        if ($user->role->name !== 'admin' && $user->id !== $observation->user_id) {
            return response()->json([
                'message' => 'You do not have permission to update observations created by other users.',
            ], 403);
        }

        $validated = $request->validate([
            'temperature' => 'sometimes|numeric|between:-50,60',
            'humidity' => 'sometimes|numeric|between:0,100',
            'pressure' => 'sometimes|numeric|between:800,1200',
            'wind_speed' => 'nullable|numeric|min:0',
            'wind_direction' => 'nullable|numeric|between:0,360',
            'precipitation' => 'nullable|numeric|min:0',
        ]);

        $observation->update($validated);

        return response()->json([
            'message' => 'Observation updated successfully.',
            'observation' => $observation,
        ]);
    }

    /**
     * Remove the specified observation from storage.
     * 
     * @param Observation $observation
     * @return JsonResponse
     */
    public function destroy(Observation $observation): JsonResponse
    {
        $user = Auth::user();

        if ($user->role->name !== 'admin' && $user->id !== $observation->user_id) {
            return response()->json([
                'message' => 'You do not have permission to delete observations created by other users.',
            ], 403);
        }

        $observation->delete();

        return response()->json([
            'message' => 'Observation deleted successfully.',
        ], 200);
    }
}
