<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
//use Laravel\Scout\Searchable;

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
        'location_id',
        'bio',
        'car_id'
    ];

    protected $hidden = [
        'password'
    ];

    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function travels()
    {
        return $this->belongsToMany(Travel::class, 'user_travel');
    }

}

