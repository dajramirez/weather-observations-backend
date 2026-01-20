<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $location
 * @property int $altitude
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Alert> $alerts
 * @property-read int|null $alerts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Observation> $observations
 * @property-read int|null $observations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Report> $reports
 * @property-read int|null $reports_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Database\Factories\StationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Station newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Station newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Station query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Station whereAltitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Station whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Station whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Station whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Station whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Station whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Station whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Station extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'location', 'altitude', 'description'];

    /**
     * A station has many observations.
     */
    public function observations()
    {
        return $this->hasMany(Observation::class);
    }

    /**
     * A station has many alerts.
     */
    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }

    /**
     * A station has many reports.
     */
    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    /**
     * A station is managed by many users.
     */
    public function users()
    {
        // The second parameter specifies the pivot table name.
        return $this->belongsToMany(User::class, 'station_user');
    }
}
