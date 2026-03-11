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
        $stats = [
            'total_users' => User::count(),
            'total_stations' => Station::count(),
            'total_observations' => Observation::count(),
            'active_alerts' => Alert::where('is_active', true)->count(),
            'observatons_today' => Observation::whereDate('observed_at', today())->count(),
        ];

        return response()->json([
            'summary' => $stats,
            'recent_users' => User::with('role:id,name')->latest()->take(5)->get(),
            'top_stations' => Station::withCount('observations')
                ->orderBy('observations_count', 'desc')
                ->take(5)
                ->get(),
        ]);
    }

    // =========================================================================
    //                          REPORT MANAGEMENT
    // =========================================================================

    public function generateReport(Request $request): JsonResponse
    {
        $request->validate([
            'station_id' => 'required|exists:stations,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $observations = Observation::with('user:id,name')
            ->where('station_id', $request->station_id)
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
            'filename' => 'reporte_admin_' . $request->station_id . '.csv',
            'data' => $observations,
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
    public function index(): JsonResponse
    {
        return response()->json(User::with(['role', 'stations'])->get());
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
            'user' => $user->load('role'),
        ]);
    }

    /**
     * Delete a user.
     * 
     * @param User $user
     * @return JsonResponse
     */

    public function deleteUser(User $user): JsonResponse
    {
        $userName = $user->name;
        $user->delete();

        return response()->json([
            'message' => "User '$userName' deleted successfully.",
        ], 200);
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
        $stations = Station::with('users:id,name,email')
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
     * Assign an observer to a station.
     */
    public function assignStation(Request $request, Station $station): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => [
                'required',
                'exists:users,id',
                // Ensure the user is an observer
                function ($attribute, $value, $fail) {
                    $user = User::find($value);
                    if ($user && !$user->hasRole('observer')) {
                        $fail('The selected user must be an observer.');
                    }
                },
            ]
        ]);

        // Avoid duplicate assignment
        if ($station->users()->where('user_id', $validated['user_id'])->exists()) {
            return response()->json([
                'message' => 'This observer is already assigned to the station.',
            ], 422);
        }

        $station->users()->attach($validated['user_id']);

        return response()->json([
            'message' => "Observer assigned to station successfully.",
            'station' => $station->load('users'),
        ]);
    }

    /**
     * Unassign an observer from a station.
     */
    public function unassignStation(Request $request, Station $station): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $station->users()->detach($validated['user_id']);

        return response()->json([
            'message' => "Observer unassigned from station successfully.",
            'station' => $station->load('users'),
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
