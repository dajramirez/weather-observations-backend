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

/*
|--------------------------------------------------------------------------
| Public API Routes
|--------------------------------------------------------------------------
| This routes don't require a valid Sanctum token.
*/

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

        // Assignation of stations to observers
        Route::post('/stations/{station}/assign', [AdminController::class, 'assignStation']);
        Route::delete('/stations/{station}/unassign', [AdminController::class, 'unassignStation']);

        // Enable or disable an alert
        Route::patch('/alerts/{alert}/toggle-active', [AlertController::class, 'togleActive']);

        //
        Route::get('/users', [AdminController::class, 'index']);
        Route::patch('/users/{user}/role', [AdminController::class, 'updateUserRole']);
        Route::delete('/users/{user}', [AdminController::class, 'destroyUser']);
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
        Route::get('/alerts', [AlertController::class, 'index']);
    });
});
