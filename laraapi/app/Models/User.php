<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    public $timestamps = false;

    protected $fillable = [
        'firstname',
        'lastname',
        'email',
        'password',
        'phone',
        'streetnum',
        'city_id',
        'car_id'
    ];

    protected $hidden = [
        'password'
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    public function travels()
    {
        return $this->belongsToMany(Travel::class, 'user_travel');
    }
}

