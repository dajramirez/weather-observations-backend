<?php

namespace App\Http\Controllers;

use App\Models\Station;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StationController extends Controller
{
    /**
     * List stations based on user role.
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            $stations = Station::all();
        } else {
            $stations = $user->stations()->get();
        }

        return response()->json($stations);
    }

    /**
     * Display the details of a specific station.
     */
    public function show(Station $station): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasRole('admin')) {
            $isAssigned = $user->stations()->where('stations.id', $station->id)->exists();
            if (!$isAssigned) {
                return response()->json([
                    'message' => 'You do not have permission to view this station.',
                ], 403);
            }
        }

        return response()->json($station);
    }
}
