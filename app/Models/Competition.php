<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Competition extends Model
{
    protected $fillable = [
        'id',
        'name', 
        'status',
        'slug',
        'description',
        'introduction',
        'cycle',
        'objectives',
        'requirements',
        'prizes',
        'categories',
        'tracks'
    ];

    protected $casts = [
        'objectives' => 'array',
        'requirements' => 'array',
        'prizes' => 'array',
        'categories' => 'array',
        'tracks' => 'array',
    ];

    public function archives()
    {
        return $this->belongsToMany(Archive::class);
    }

}
