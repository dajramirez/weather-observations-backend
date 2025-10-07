<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    protected $fillable = ['station_id', 'title', 'message', 'level', 'start_at'];

    public function station()
    {
        return $this->belongsTo(Station::class);
    }
}
