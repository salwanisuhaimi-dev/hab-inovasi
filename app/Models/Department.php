<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = ['id','name', 'code', 'description', 'status'];

    public function users()
    {
        return $this->hasMany(User::class, 'google_id');
    }
}
