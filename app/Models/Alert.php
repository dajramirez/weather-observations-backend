<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $station_id
 * @property string $title
 * @property string $message
 * @property string $level
 * @property int $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Station $station
 * @method static \Database\Factories\AlertFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Alert newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Alert newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Alert query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Alert whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Alert whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Alert whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Alert whereLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Alert whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Alert whereStationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Alert whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Alert whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
