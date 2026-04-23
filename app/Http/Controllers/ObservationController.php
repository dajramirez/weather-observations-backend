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
     * Display a list of the last observations.
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();

        $query = Observation::with(['station', 'user:id,name']);

        if (!$user->hasRole('admin')) {
            $stationsIds = $user->stations->pluck('id');
            $query->whereIn('station_id', $stationsIds);
        }

        return response()->json($query->latest('observed_at')->paginate(15));
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

        // Check if the observer has assigned this station
        if (!$user->hasRole('admin')) {
            $isAssigned = $user->stations()->where('stations.id', $validated['station_id'])->exists();
            if (!$isAssigned) {
                return response()->json([
                    'message' => 'No tiene permiso para registrar observaciones en esta estación.',
                ], 403);
            }
        }

        $observation = Observation::create(array_merge($validated, ['user_id' => $user->id,]));

        // Automatic alert checking
        $this->checkAndCreateAlerts($observation);

        return response()->json([
            'message' => 'Observación creada exitosamente.',
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
        $alerts = [];

        // Temperature
        if ($observation->temperature !== null) {
            if ($observation->temperature >= 42) {
                $alerts[] = [
                    'title' => 'Calor extremo',
                    'message' => "Temperatura extremadamente alta registrada: {$observation->temperature}°C. Riesgo severo para la salud.",
                    'level' => 'red',
                ];
            } elseif ($observation->temperature >= 35) {
                $alerts[] = [
                    'title' => 'Ola de calor',
                    'message' => "Temperatura muy alta registrada: {$observation->temperature}°C. Precaución.",
                    'level' => 'orange',
                ];
            } elseif ($observation->temperature <= -10) {
                $alerts[] = [
                    'title' => 'Frío extremo',
                    'message' => "Temperatura extremadamente baja registrada: {$observation->temperature}°C. Riesgo de hipotermia.",
                    'level' => 'red',
                ];
            } elseif ($observation->temperature <= 0) {
                $alerts[] = [
                    'title' => 'Helada',
                    'message' => "Temperatura bajo cero registrada: {$observation->temperature}°C. Posible formación de hielo.",
                    'level' => 'orange',
                ];
            }
        }

        // Wind speed
        if ($observation->wind_speed !== null) {
            if ($observation->wind_speed >= 90) {
                $alerts[] = [
                    'title' => 'Viento huracanado',
                    'message' => "Velocidad del viento extrema: {$observation->wind_speed} km/h. Peligro muy alto.",
                    'level' => 'red',
                ];
            } elseif ($observation->wind_speed >= 60) {
                $alerts[] = [
                    'title' => 'Viento fuerte',
                    'message' => "Viento fuerte registrado: {$observation->wind_speed} km/h. Precaución.",
                    'level' => 'orange',
                ];
            } elseif ($observation->wind_speed >= 40) {
                $alerts[] = [
                    'title' => 'Viento moderado',
                    'message' => "Viento moderado registrado: {$observation->wind_speed} km/h.",
                    'level' => 'yellow',
                ];
            }
        }

        // Precipitation
        if ($observation->precipitation !== null) {
            if ($observation->precipitation >= 50) {
                $alerts[] = [
                    'title' => 'Lluvia torrencial',
                    'message' => "Precipitación muy intensa: {$observation->precipitation} mm. Riesgo de inundaciones.",
                    'level' => 'red',
                ];
            } elseif ($observation->precipitation >= 20) {
                $alerts[] = [
                    'title' => 'Lluvia intensa',
                    'message' => "Precipitación intensa registrada: {$observation->precipitation} mm.",
                    'level' => 'orange',
                ];
            }
        }

        // Pressure
        if ($observation->pressure !== null) {
            if ($observation->pressure <= 970) {
                $alerts[] = [
                    'title' => 'Presión muy baja',
                    'message' => "Presión atmosférica muy baja: {$observation->pressure} hPa. Posible temporal.",
                    'level' => 'orange',
                ];
            } elseif ($observation->pressure >= 1040) {
                $alerts[] = [
                    'title' => 'Presión muy alta',
                    'message' => "Presión atmosférica muy alta: {$observation->pressure} hPa.",
                    'level' => 'yellow',
                ];
            }
        }

        // Humidity
        if ($observation->humidity !== null) {
            if ($observation->humidity >= 95) {
                $alerts[] = [
                    'title' => 'Humedad extrema',
                    'message' => "Humedad muy alta registrada: {$observation->humidity}%. Riesgo de niebla densa.",
                    'level' => 'yellow',
                ];
            }
        }

        foreach ($alerts as $alertData) {
            Alert::create(array_merge($alertData, [
                'station_id' => $observation->station_id,
                'observation_id' => $observation->id,
                'is_active' => true,
            ]));
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
                'message' => 'No tiene permiso para ver esta observación.',
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
                'message' => 'No tiene permiso para actualizar observaciones creadas por otros usuarios.',
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
            'message' => 'Observación actualizada exitosamente.',
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
                'message' => 'No tiene permiso para eliminar esta observación.',
            ], 403);
        }

        $observation->delete();

        return response()->json([
            'message' => 'Observación eliminada exitosamente.',
        ], 200);
    }
}
