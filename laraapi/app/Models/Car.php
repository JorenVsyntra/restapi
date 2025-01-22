<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'carseats',
        'name',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function travels()
    {
        return $this->hasMany(Travel::class);
    }
}