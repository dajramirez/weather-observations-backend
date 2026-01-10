<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use App\Models\Station;
use App\Models\Observation;
use App\Models\Role;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{

    // =========================================================================
    //                              DASHBOARD
    // =========================================================================

    /**
     * Get a summary of the system status.
     * 
     * @return JsonResponse
     */

    public function dashboard(): JsonResponse
    {
        // Total counts for key entities
        $totalUsers = User::count();
        $totalStations = Station::count();
        $totalObservations = Observation::count();
        $activeAlerts = Alert::where('is_active', true)->count();

        // Get the last 5 users registered
        $latestUsers = User::with('role')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get(['id', 'name', 'email', 'role_id', 'created_at']);

        // Get the top 5 stations by number of observations
        $topStations = Station::withCount('observations')
            ->orderBy('observations_count', 'desc')
            ->take(5)
            ->get(['id', 'name', 'location', 'observations_count']);

        return response()->json([
            'summary' => [
                'total_users' => $totalUsers,
                'total_stations' => $totalStations,
                'total_observations' => $totalObservations,
                'active_alerts' => $activeAlerts,
            ],
            'latest_users' => $latestUsers,
            'top_stations' => $topStations,
        ]);
    }

    // =========================================================================
    //                          USER MANAGEMENT
    // =========================================================================

    /**
     * List all users with their roles.
     * 
     * @return JsonResponse
     */
    public function listUsers(): JsonResponse
    {
        $users = User::with('role:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role_id', 'created_at']);

        $roles = Role::all(['id', 'name']);

        return response()->json([
            'users' => $users,
            'available_roles' => $roles,
        ]);
    }

    /**
     * Update the role of a specific user.
     * 
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */

    public function updateUserRole(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            // Ensure the role exists in the roles table
            'role_id' => 'required|exists:roles,id',
        ]);

        $user->update(['role_id' => $validated['role_id']]);

        return response()->json([
            'message' => 'User role updated successfully.',
            'user' => $user->load('role:id,name'),
        ]);
    }

    // =========================================================================
    //                      STATION MANAGEMENT (CRUD)
    // =========================================================================

    /**
     * List all stations, including their associated users (observers).
     * 
     * @return JsonResponse
     */

    public function listStations(): JsonResponse
    {
        $stations = Station::with('user:id,name,email')
            ->orderBy('name')
            ->get();

        // Get all observers (users with role_id = 2) to facilitate assignment
        $observerRoleId = Role::where('name', 'observer')->first()->id ?? null;
        $observers = [];
        if ($observerRoleId) {
            $observers = User::where('role_id', $observerRoleId)
                ->orderBy('name')
                ->get(['id', 'name', 'email']);
        }

        return response()->json([
            'stations' => $stations,
            'observers' => $observers,
        ]);
    }

    /**
     * Create a new station.
     * 
     * @param Request $request
     * @return JsonResponse
     */

    public function createStation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:stations,name',
            'location' => 'required|string|max:255',
            'altitude' => 'required|integer|min:0',
            'description' => 'nullable|string',
        ]);

        $station = Station::create($validated);

        return response()->json([
            'message' => 'Station created successfully.',
            'station' => $station,
        ], 201);
    }

    /**
     * Update an existing station.
     * 
     * @param Request $request
     * @param Station $station
     * @return JsonResponse
     */

    public function updateStation(Request $request, Station $station): JsonResponse
    {
        $validated = $request->validate([
            // Name must be unique, ignoring the current station's name.
            'name' => ['required', 'string', 'max:255', Rule::unique('stations', 'name')->ignore($station->id)],
            'location' => 'required|string|max:255',
            'altitude' => 'required|integer|min:0',
            'description' => 'nullable|string',
        ]);

        $station->update($validated);

        return response()->json([
            'message' => 'Station updated successfully.',
            'station' => $station,
        ]);
    }

    /**
     * Delete a station (cascades to observations, alerts, and reports).
     * 
     * @param Station $station
     * @return JsonResponse
     */

    public function deleteStation(Station $station): JsonResponse
    {
        $stationName = $station->name;
        $station->delete();

        return response()->json([
            'message' => "Station '$stationName' deleted successfully.",
        ], 200);
    }

    // =========================================================================
    //                      STATION ASSIGNMENT (OBSERVERS)
    // =========================================================================

    /**
     * Assign or unassign an observer to/from a station.
     * 
     * @param Request $request
     * @param Station $station
     * @return JsonResponse
     */

    public function assignObserver(Request $request, Station $station): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            // 'action' must be either 'assign' or 'unassign'
            'action' => ['required', 'string', Rule::in(['assign', 'unassign'])],
        ]);

        $userId = $validated['user_id'];
        $user = User::findOrFail($userId);

        // Check if the user is an observer
        if (!$user->hasRole('observer') && !$user->hasRole('admin')) {
            return response()->json([
                'message' => 'User must be an observer or admin to be assigned to a station.',
            ], 422);
        }

        if ($validated['action'] === 'assign') {
            // Attach the user to the station (if not already attached)
            $station->user()->syncWithoutDetaching([$userId]);
            $message = "User '{$user->name}' successfully assigned to station '{$station->name}'.";
        } else {
            // Detach the user from the station
            $station->user()->detach($userId);
            $message = "User '{$user->name}' successfully unassigned from station '{$station->name}'.";
        }

        // Reload the station with users to return the updated list.
        $station->load('users:id,name,email');

        return response()->json([
            'message' => $message,
            'station' => $station,
        ]);
    }

    // =========================================================================
    //                          ALERT MANAGEMENT (CRUD)
    // =========================================================================

    /**
     * List all alerts in the system
     * 
     * @return JsonResponse
     */

    public function listAlerts(): JsonResponse
    {
        $alerts = Alert::with('station:id,name')
            ->orderBy('is_active', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $stations = Station::all(['id', 'name']);

        return response()->json([
            'alerts' => $alerts,
            'stations' => $stations,
        ]);
    }

    /**
     * Create a new alert
     * 
     * @param Request $request
     * @return JsonResponse
     */

    public function createAlert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'station_id' => 'required|exists:stations,id',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            // Level validation must be one of the enum values
            'level' => ['required', 'string', Rule::in(['red', 'orange', 'yellow', 'green'])],
            'is_active' => 'boolean',
        ]);

        $alert = Alert::create($validated);

        return response()->json([
            'message' => 'Alert created successfully.',
            'alert' => $alert->load('station:id,name'),
        ], 201);
    }

    /**
     * Update an existing alert
     * 
     * @param Request $request
     * @param Alert $alert
     * @return JsonResponse
     */

    public function updateAlert(Request $request, Alert $alert): JsonResponse
    {
        $validated = $request->validate([
            'station_id' => 'required|exists:stations,id',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            // Level validation must be one of the enum values
            'level' => ['required', 'string', Rule::in(['red', 'orange', 'yellow', 'green'])],
            'is_active' => 'boolean',
        ]);

        $alert->update($validated);

        return response()->json([
            'message' => 'Alert updated successfully.',
            'alert' => $alert->load('station:id,name'),
        ]);
    }

    /**
     * Delete an alert
     * 
     * @param Alert $alert
     * @return JsonResponse
     */

    public function deleteAlert(Alert $alert): JsonResponse
    {
        $alert->delete();

        return response()->json([
            'message' => "Alert deleted successfully.",
        ], 200);
    }
}
