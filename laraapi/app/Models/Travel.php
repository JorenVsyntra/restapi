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
        'km'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_travel');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}