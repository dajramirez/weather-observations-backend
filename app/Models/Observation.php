<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $station_id
 * @property int $user_id
 * @property string $observed_at
 * @property float $temperature
 * @property float $humidity
 * @property float $pressure
 * @property string $wind_direction
 * @property float $wind_speed
 * @property float|null $precipitation
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Station $station
 * @property-read \App\Models\User $user
 * @method static \Database\Factories\ObservationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Observation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Observation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Observation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Observation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Observation whereHumidity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Observation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Observation whereObservedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Observation wherePrecipitation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Observation wherePressure($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Observation whereStationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Observation whereTemperature($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Observation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Observation whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Observation whereWindDirection($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Observation whereWindSpeed($value)
 * @mixin \Eloquent
 */
class Observation extends Model
{
    use HasFactory;

    protected $fillable = ['station_id', 'user_id', 'temperature', 'precipitation', 'humidity', 'pressure', 'wind_speed', 'wind_direction', 'observed_at'];

    public function station()
    {
        return $this->belongsTo(Station::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
