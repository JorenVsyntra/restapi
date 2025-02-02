<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Travel extends Model
{
    use Searchable;
    public $timestamps = false;
    protected $table = 'travels'; 
    protected $fillable = [
        'startlocation_id',
        'destination_id',
        'date',
        'user_id',
        'fee',
        'km',
        'price',
        'car_id',
        'av_seats'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_travel');
    }
    
    public function destinationLocation()
    {
        return $this->belongsTo(Location::class, 'destination_id');
    }

    public function startLocation()
    {
        return $this->belongsTo(Location::class, 'startlocation_id');
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