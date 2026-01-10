<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
