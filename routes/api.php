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

// Admin, observer or user access
Route::post('/login', [AuthController::class, 'login']);

// Register a new user (currently optional)
Route::post('/register', [AuthController::class, 'register']);

// Public route with the last observations and alerts
// NOTE: It will require a real PublicDataController for DB data
Route::get('/home', [PublicDataController::class, 'index']);

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

    /*
    |--------------------------------------------------------------------------
    | Admin API Routes
    |--------------------------------------------------------------------------
    | All the routes inside this group require a valid Sanctum token and role admin.
    */

    Route::middleware('role:admin')->prefix('admin')->group(function () {

        // Stations, observations and alerts general summary
        Route::get('/dashboard', [AdminController::class, 'dashboard']);

        // Meteorological stations CRUD (Using StationController)
        Route::apiResource('stations', StationController::class);

        // Assignation of stations to observers
        // NOTE: This could be a method inside AdminController or UserController
        Route::post('/stations/{station}/assign-observer', [AdminController::class, 'assignObserverToStation']);

        // Complete alerts management
        Route::apiResource('alerts', AlertController::class);

        // Generation of reports (PDF, CSV, etc.)
        Route::get('/reports', [AdminController::class, 'generateReports']);
    });

    /*
    |--------------------------------------------------------------------------
    | Observer API Routes
    |--------------------------------------------------------------------------
    | All the routes inside this group require a valid Sanctum token and role observer.
    */

    Route::middleware('role:observer')->prefix('observer')->group(function () {

        // Summary of assigned stations and recent observations
        Route::get('/dashboard', [ObserverController::class, 'dashboard']);

        // Listing and form to create new observations
        Route::apiResource('/observations', ObservationController::class);

        // Generation of observation reports
        Route::get('/reports', [ObserverController::class, 'generateReports']);

        // Visualization of active alerts (read-only)
        Route::get('/alerts', [AlertController::class, 'index']);
    });

    /*
    |--------------------------------------------------------------------------
    | User API Routes
    |--------------------------------------------------------------------------
    | All the routes inside this group require a valid Sanctum token and role user.
    */

    Route::middleware('role:user')->prefix('user')->group(function () {

        // Last available observations
        Route::get('/dashboard', [AppUserController::class, 'dashboard']);

        // Open reports (read-only)
        Route::get('/reports', [AppUserController::class, 'listReports']);

        // Active alerts (read-only)
        Route::get('/alerts', [AlertController::class, 'index']);
    });
});
