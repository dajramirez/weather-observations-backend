<?php

namespace App\Http\Controllers;

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
}
