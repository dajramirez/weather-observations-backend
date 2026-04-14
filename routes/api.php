<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StationController;
use App\Http\Controllers\ObservationController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\AppUserController;
use App\Http\Controllers\ObserverController;
use App\Http\Controllers\PublicDataController;

/*
|--------------------------------------------------------------------------
| Public API Routes
|--------------------------------------------------------------------------
| This routes don't require a valid Sanctum token.
*/

Route::get('/public', [PublicDataController::class, 'index']);
Route::get('/public/stations', [PublicDataController::class, 'stations']);
Route::get('/public/stations/{stationId}/observations', [PublicDataController::class, 'latestObservations']);
Route::get('/public/search', [PublicDataController::class, 'search']);

// Admin, observer or user access
Route::post('/login', [AuthController::class, 'login']);

// Register a new user (currently optional)
Route::post('/register', [AuthController::class, 'register']);

/*
|--------------------------------------------------------------------------
| Protected API Routes
|--------------------------------------------------------------------------
| All the routes inside this group require a valid Sanctum token.
*/

Route::middleware('auth:sanctum')->group(function () {

    // Logout the authenticated user (revoke the token)
    Route::post('/logout', [AuthController::class, 'logout']);

    // Endpoint to get the authenticated user's details
    Route::get('/user', function (Request $request) {
        return $request->user()->load('role');
    });

    // Routes for stations (StationController will handle role-based access)
    Route::get('/stations', [StationController::class, 'index']);
    Route::get('/stations/{station}', [StationController::class, 'show']);

    /*
    |--------------------------------------------------------------------------
    | Admin API Routes
    |--------------------------------------------------------------------------
    | All the routes inside this group require a valid Sanctum token and role admin.
    */

    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/reports', [AdminController::class, 'generateReport']);
        Route::get('/reports/history', [AdminController::class, 'listReports']);
        Route::patch('/reports/{report}/toggle-public', [AdminController::class, 'togglePublic']);

        // Users
        Route::get('/users', [AdminController::class, 'index']);
        Route::patch('/users/{user}/role', [AdminController::class, 'updateUserRole']);
        Route::delete('/users/{user}', [AdminController::class, 'deleteUser']);

        // Stations
        Route::get('/stations', [AdminController::class, 'listStations']);
        Route::post('/stations', [AdminController::class, 'createStation']);
        Route::patch('/stations/{station}', [AdminController::class, 'updateStation']);
        Route::delete('/stations/{station}', [AdminController::class, 'deleteStation']);
        Route::post('/stations/{station}/assign', [AdminController::class, 'assignStation']);
        Route::delete('/stations/{station}/unassign', [AdminController::class, 'unassignStation']);

        // Observations
        Route::get('/observations', [ObservationController::class, 'index']);
        Route::post('/observations', [ObservationController::class, 'store']);
        Route::get('/observations/{observation}', [ObservationController::class, 'show']);
        Route::patch('/observations/{observation}', [ObservationController::class, 'update']);
        Route::delete('/observations/{observation}', [ObservationController::class, 'destroy']);

        // Alerts
        Route::get('/alerts', [AdminController::class, 'listAlerts']);
        Route::post('/alerts', [AdminController::class, 'createAlert']);
        Route::patch('/alerts/{alert}', [AdminController::class, 'updateAlert']);
        Route::delete('/alerts/{alert}', [AdminController::class, 'deleteAlert']);
        Route::patch('/alerts/{alert}/toggle-active', [AlertController::class, 'togleActive']);
    });

    /*
    |--------------------------------------------------------------------------
    | Observer API Routes
    |--------------------------------------------------------------------------
    | All the routes inside this group require a valid Sanctum token and role observer.
    */

    Route::middleware('role:observer')->prefix('observer')->group(function () {
        Route::get('/dashboard', [ObserverController::class, 'dashboard']);

        // CRUD for observations (ObservationController will handle role-based access)
        Route::get('/observations', [ObservationController::class, 'index']);
        Route::post('/observations', [ObservationController::class, 'store']);
        Route::get('/observations/{observation}', [ObservationController::class, 'show']);
        Route::patch('/observations/{observation}', [ObservationController::class, 'update']);
        Route::delete('/observations/{observation}', [ObservationController::class, 'destroy']);

        // Generation of reports (PDF, CSV, etc.)
        Route::get('/reports', [ObserverController::class, 'generateReport']);
        Route::get('/reports/history', [ObserverController::class, 'listReports']);
        Route::patch('/reports/{report}/toggle-public', [ObserverController::class, 'togglePublic']);
    });

    /*
    |--------------------------------------------------------------------------
    | Admin + Observer API Routes
    |--------------------------------------------------------------------------
    | All the routes inside this group require a valid Sanctum token and role admin or observer.
    */

    Route::middleware('role:admin,observer')->group(function () {
        Route::get('alerts', [AlertController::class, 'index']);
        Route::get('alerts/{alert}', [AlertController::class, 'show']);
    });

    /*
    |--------------------------------------------------------------------------
    | User API Routes
    |--------------------------------------------------------------------------
    | All the routes inside this group require a valid Sanctum token and role user.
    */

    Route::middleware('role:user')->prefix('user')->group(function () {
        Route::get('/dashboard', [AppUserController::class, 'dashboard']);
        Route::get('/reports', [AppUserController::class, 'listReports']);
        Route::get('/alerts', [AppUserController::class, 'listAlerts']);
    });
});
