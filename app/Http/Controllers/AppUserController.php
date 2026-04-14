<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Report;

class AppUserController extends Controller
{
    public function listReports(): JsonResponse
    {
        $reports = Report::with(['station:id,name', 'user:id,name'])
            ->where('is_public', true)
            ->latest()
            ->get();

        return response()->json($reports);
    }

    public function listAlerts(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page', 15);

        $alerts = Alert::with('station:id,name')
            ->where('is_active', true)
            ->latest()
            ->paginate($perPage);

        return response()->json($alerts);
    }
}
