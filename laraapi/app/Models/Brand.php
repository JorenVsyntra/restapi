<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    public $timestamps = false;
    protected $fillable = ['name'];

    public function cars()
    {
        return $this->hasMany(Car::class);
    }
}