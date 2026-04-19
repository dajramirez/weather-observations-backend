<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = ['station_id', 'observation_id', 'title', 'message', 'level', 'is_active'];

    // Ensure is_active is treated as a boolean (In Database it's TINYINT)
    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function station()
    {
        return $this->belongsTo(Station::class);
    }

    public function observation()
    {
        return $this->belongsTo(Observation::class);
    }
}
