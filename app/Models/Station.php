<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Station extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'location', 'altitude', 'description'];

    public function observations()
    {
        return $this->hasMany(Observation::class);
    }

    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    public function users()
    {
        // The second parameter specifies the pivot table name.
        return $this->belongsToMany(User::class, 'station_user');
    }
}
