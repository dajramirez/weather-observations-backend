<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Alert;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AlertController extends Controller
{
    /**
     * Display a list of alerts.
     * If role is admin, show all alerts.
     * If role is observer, show alerts related to their stations.
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            $alerts = Alert::with(['station', 'observation'])->latest()->get();
        } else {
            // Get IDs of stations assigned to the observer
            $stationIds = $user->stations()->pluck('stations.id');

            $alerts = Alert::whereIn('station_id', $stationIds)
                ->with(['station', 'observation'])
                ->latest()
                ->get();
        }

        return response()->json($alerts);
    }

    /**
     * Display the specified alert.
     * 
     * @param Alert $alert
     * @return JsonResponse
     */
    public function show(Alert $alert): JsonResponse
    {
        $user = Auth::user();

        // Check if the user has access to the alert
        if (!$user->hasRole('admin')) {
            $isAssigned = $user->stations()->where('stations.id', $alert->station_id)->exists();
            if (!$isAssigned) {
                return response()->json([
                    'message' => 'You do not have permission to view this alert.',
                ], 403);
            }
        }

        return response()->json($alert->load(['station', 'observation']));
    }

    /**
     * Disable an alert (only for admin users).
     * 
     * @param Alert $alert
     * @return JsonResponse
     */
    public function togleActive(Alert $alert): JsonResponse
    {
        // Although middleware should handle this, double-checking here
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'message' => 'You do not have permission to perform this action.',
            ], 403);
        }

        $alert->is_active = !$alert->is_active;
        $alert->save();

        return response()->json([
            'message' => $alert->is_active ? 'Alert activated.' : 'Alert deactivated.',
            'alert' => $alert,
        ]);
    }
}
