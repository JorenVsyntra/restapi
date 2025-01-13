<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Travel extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'startlocation',
        'destination',
        'date',
        'user_id',
        'fee',
        'km',
        'car_id'
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