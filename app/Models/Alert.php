<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = ['station_id', 'title', 'message', 'level', 'start_at', 'end_at', 'is_active'];

    public function station()
    {
        return $this->belongsTo(Station::class);
    }
}
