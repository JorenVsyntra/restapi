<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Travel extends Model
{
    use Searchable;
    public $timestamps = false;
    protected $fillable = [
        'startlocation',
        'destination',
        'date',
        'user_id',
        'fee',
        'km',
        'car_id',
        'av_seats'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_travel');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function car()
    {
        return $this->belongsTo(Car::class, 'car_id');
    }
}